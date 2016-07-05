<?php

class Nfe_APIRequest {
  public function __construct() {}

  private function _defaultHeaders( $headers = array() ) {
    $headers[] = "Authorization: Basic " . Nfe::getApiKey();
    $headers[] = "Content-Type: application/json";
    $headers[] = "Accept: application/json";
    $headers[] = "Accept-Charset: utf-8";
    $headers[] = "User-Agent: NFe.io PHP Library";
    $headers[] = "Accept-Language: pt-br;q=0.9,pt-BR";

    return $headers;
  }

  public function request( $method, $url, $data = array() ) {
    global $last_api_response_code;

    if ( Nfe::getApiKey() == null ) {
      Nfe_Utilities::authFromEnv();
    }

    if ( Nfe::getApiKey() == null ) {
      return new NfeAuthenticationException("Chave de API não configurada. Utilize Nfe::setApiKey(...) para configurar.");
    }

    $headers = $this->_defaultHeaders();

    list( $response_body, $response_code ) = $this->requestWithCURL( $method, $url, $headers, $data );
    if ($response_code == 302) {
      $response = $response_body;
    } else {
      $response = json_decode($response_body);
    }

    if ( json_last_error() != JSON_ERROR_NONE ) { 
      throw new NfeObjectNotFound($response_body);
    }

    if ( $response_code == 404 ) {
      throw new NfeObjectNotFound($response_body);
    }

    if ( isset($response->errors) ) {
      if ( (gettype($response->errors) != "string") && count(get_object_vars($response->errors)) == 0) {
        unset($response->errors);
      }
      elseif ((gettype($response->errors) != "string") && count(get_object_vars($response->errors)) > 0) {
        $response->errors = (array) $response->errors;
      }

      if (isset($response->errors) && (gettype($response->errors) == "string")) {
        $response->errors = $response->errors;
      }
    }

    $last_api_response_code = $response_code;

    return $response;
  }

  private function requestWithCURL( $method, $url, $headers, $data = array() ) {
    $curl   = curl_init();
    $opts   = array();
    $data   = Nfe_Utilities::arrayToParams($data);
    $method = strtolower($method);

    if ($method == "post") {
      $opts[CURLOPT_POST]       = 1;
      $opts[CURLOPT_POSTFIELDS] = $data;
    }
    elseif ($method == "delete") {
      $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }
    elseif ($method == "put") {
      $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
      $opts[CURLOPT_POSTFIELDS]    = $data;
    }

    $opts[CURLOPT_URL]            = $url;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_FOLLOWLOCATION] = FALSE;
    $opts[CURLOPT_HEADER]         = TRUE;
    $opts[CURLOPT_CONNECTTIMEOUT] = 30;
    $opts[CURLOPT_TIMEOUT]        = 80;
    $opts[CURLOPT_HTTPHEADER]     = $headers;

    $opts[CURLOPT_SSL_VERIFYHOST] = 2;
    $opts[CURLOPT_SSL_VERIFYPEER] = false; // true
    $opts[CURLOPT_CAINFO]         = realpath( dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "data" ) . DIRECTORY_SEPARATOR . "ca-bundle.crt";

    curl_setopt_array($curl, $opts);
    
    curl_setopt( $curl, CURLOPT_PROXY, "127.0.0.1:8888" );
    // curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
    curl_setopt( $curl, CURLOPT_VERBOSE, 1 );


    $response        = curl_exec($curl);
    $response_code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    $header_size     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $response_header = substr($response, 0, $header_size);
    $response_body   = substr($response, $header_size);

    // if we have a redirect we need to get the location header
    if ($response_code == 302) {
      preg_match_all('/^Location:\s?(.*)$/mi', $response, $matches);

      return array(trim($matches[1][0]), $response_code);
    }

    curl_close($curl);

    return array($response_body, $response_code);
  }
}
