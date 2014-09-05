<?php
/**
 * This project is intended to wrap around the API for http://elt.li
 */
namespace EricHansen\ELTPHP;

class ELTPHP {
    /**
     * Can pass either the ID and secret or a file ocntaining these.
     *
     * @param string $client_id The client ID generated via the API
     * @param string $client_secret Client secret generated via API
     * @param string $config_file File containing client_id and client_secret JSON entries
     */
    public function __construct($client_id = "", $client_secret = "", $config_file = ""){
        if(empty($client_id) && empty($client_secret) && !empty($config_file)){
            $conf = @json_decode(file_get_contents($config_file));
        } else{
            $conf = (object)array("id" => $client_id, "secret" => $client_secret);
        }

        if($conf){
            $auth = $this->_api_call("oauth/token", array(
                "grant_type" => "client_credentials",
                "client_id" => $conf->id,
                "client_secret" => $conf->secret), "POST");

            if(!isset($auth->code) || ($auth->code < 400))
                $this->token = $auth->access_token;

            unset($conf);
        }
    }

    /**
     * @param $uri URI to access for API (i.e.: oauth/token for generating a new access token)
     * @param array $data Any data to be passed to the call
     * @param string $method Verb to call (GET/POST/DELETE/etc...)
     * @return mixed Object of JSON-formatted response by ELI.it server
     */
    private function _api_call($uri, $data = array(), $method="GET"){
        if(!function_exists('curl_init')){
            die("PHP-cURL needs to be installed!");
        }

        $args = $data;

        if($method == "GET"){
            $uri .= http_build_query($args);
            $args = null;
        }

        $curl_opts = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CUSTOMREQUEST => $method
        );

        if($args)
            $curl_opts[CURLOPT_POSTFIELDS] = http_build_query($args);

        /**
         * Add Bearer auth token HTTP header here...
         */
        if(isset($this->token))
            $curl_opts[CURLOPT_HTTPHEADER] = array('Authorization' => "Bearer ".$this->token);

        $ch = curl_init("http://elt.li/" . $uri);
        curl_setopt_array(
            $ch,
            $curl_opts
        );

        $resp = curl_exec($ch);

        curl_close($ch);

        return json_decode($resp);
    }
}
