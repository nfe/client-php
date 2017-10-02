<?php

class NFe_APIRequest {
  public function __construct() {}

  private function _defaultHeaders( $headers = array() ) {
    $headers[] = "Authorization: Basic " . NFe::getApiKey();
    $headers[] = "Content-Type: application/json";
    $headers[] = "Accept: application/json";
    $headers[] = "Accept-Charset: UTF-8";
    $headers[] = "User-Agent: NFe.io PHP Library";
    $headers[] = "Accept-Language: pt-br;q=0.9,pt-BR";

    return $headers;
  }

  public function request( $method, $url, $data = array() ) {
    global $last_api_response_code;

    if ( NFe::getApiKey() == null ) {
      NFe_Utilities::authFromEnv();
    }

    if ( NFe::getApiKey() == null ) {
      return new NFeAuthenticationException("Chave de API não configurada. Utilize NFe::setApiKey(...) para configurar.");
    }

    $headers = $this->_defaultHeaders();

    list( $response_body, $response_code ) = $this->requestWithCURL( $method, $url, $headers, $data );
    $response = json_encode("{}");

    if ( $response_code == 302 ) {
      $response = $response_body;
    }
    else if (!empty($response_body)) {
      $response = json_decode($response_body);
      if ( json_last_error() != JSON_ERROR_NONE ) {
        throw new NFeInvalidResponseBody($response_body);
      }
    }

    if ( $response_code == 403 ) {
      throw new NFeAuthorizationException($response_body);
    }

    if ( $response_code == 409 ) {
      throw new NFeObjectAlreadyExists($response_body);
    }

    if ( $response_code == 404 ) {
      throw new NFeObjectNotFound($response_body);
    }

    if ( $response_code == 400 ) {
      throw new NFeException($response->message);
    }

    if ( isset($response->errors) ) {
      if ( ( gettype($response->errors) != "string") && count( get_object_vars($response->errors) ) == 0 ) {
        unset($response->errors);
      }
      elseif ( ( gettype($response->errors) != "string") && count( get_object_vars($response->errors) ) > 0 ) {
        $response->errors = (array) $response->errors;
      }

      if ( isset($response->errors) && ( gettype($response->errors) == "string") ) {
        $response->errors = $response->errors;
      }
    }

    $last_api_response_code = $response_code;

    return $response;
  }

  private function requestWithCURL( $method, $url, $headers, $data = array() ) {
    $curl   = curl_init();
    $data   = NFe_Utilities::arrayToParams($data);
    $method = strtolower($method);
    $opts   = array();

    if ($method == 'post') {
      $opts[CURLOPT_POST]       = 1;
      $opts[CURLOPT_POSTFIELDS] = $data;
    }
    elseif ($method == 'delete') {
      $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }
    elseif ($method == 'put') {
      $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
      $opts[CURLOPT_POSTFIELDS]    = $data;
    }

    $opts[CURLOPT_URL]            = $url;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_FOLLOWLOCATION] = FALSE;
    $opts[CURLOPT_HEADER]         = TRUE;
    $opts[CURLOPT_TIMEOUT]        = 80;
    $opts[CURLOPT_CONNECTTIMEOUT] = 30;
    $opts[CURLOPT_HTTPHEADER]     = $headers;
    if ( NFe::$verifySslCerts == false ) {
      $opts[CURLOPT_SSL_VERIFYPEER] = false;
    }
    $opts[CURLOPT_SSL_VERIFYHOST] = 2;

    // @codingStandardsIgnoreStart
    // PSR2 requires all constants be upper case. Sadly, the CURL_SSLVERSION
    // constants to not abide by those rules.
    //
    // Opt into TLS 1.x support on older versions of curl. This causes some
    // curl versions, notably on RedHat, to upgrade the connection to TLS
    // 1.2, from the default TLS 1.0.
    if ( ! defined('CURL_SSLVERSION_TLSv1') ) {
        define('CURL_SSLVERSION_TLSv1', 1); // constant not defined in PHP < 5.5
    }
    $opts[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1;
    // @codingStandardsIgnoreEnd

    curl_setopt_array($curl, $opts);

    // For debugging
    if ( NFe::$debug == true ) {
      curl_setopt( $curl, CURLOPT_PROXY, "127.0.0.1:8888" );
      curl_setopt( $curl, CURLOPT_VERBOSE, 1 );
    }

    $response = curl_exec($curl);

    if ( ! defined('CURLE_SSL_CACERT_BADFILE') ) {
      define('CURLE_SSL_CACERT_BADFILE', 77);  // constant not defined in PHP
    }

    $errno = curl_errno($curl);
    if ( $errno == CURLE_SSL_CACERT ||
      $errno == CURLE_SSL_PEER_CERTIFICATE ||
      $errno == CURLE_SSL_CACERT_BADFILE
    ) {
      array_push(
        $headers,
        'X-NFe-Client-Info: {"ca":"using NFe-supplied CA bundle"}'
      );
      $cert = self::caBundle();
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CAINFO, $cert);
      $response = curl_exec($curl);
    }

    if ( $response === false ) {
      $errno   = curl_errno($curl);
      $message = curl_error($curl);
      curl_close($curl);
      $this->handleCurlError($url, $errno, $message);
    }

    $response_code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $header_size     = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $response_header = substr($response, 0, $header_size);
    $response_body   = substr($response, $header_size);

    // if we have a redirect, we need to get the location header
    if ( $response_code == 302 ) {
      preg_match_all('/^Location:\s?(.*)$/mi', $response, $matches);
      return array(trim($matches[1][0]), $response_code);
    }

    curl_close($curl);
    return array($response_body, $response_code);
  }

  /**
   * @param string $url
   * @param number $errno
   * @param string $message
   * @throws NFeAuthenticationException
   */
  private function handleCurlError($url, $errno, $message) {
    switch ($errno) {
      case CURLE_COULDNT_CONNECT:
      case CURLE_COULDNT_RESOLVE_HOST:
      case CURLE_OPERATION_TIMEOUTED:
        $msg = "Não foi possível conectar ao ($url). Por favor, cheque sua "
         . "conexão com a internet e tente novamente. Se o problema persistir,";
        break;
      case CURLE_SSL_CACERT:
      case CURLE_SSL_PEER_CERTIFICATE:
        $msg = "Não foi possível verificar o certificado SSL do NFe. Se certifique "
         . "que sua rede não está interceptando certificados. "
         . "(Tente ir $url em seu navegador.)  "
         . "Se o problema persistir,";
        break;
      default:
        $msg = "Error inesperado ao comunicar com a API do NFe.io. " . "Se o problema persistir,";
    }
    $msg .= " entre em contato com NFe.io (https://nfe.io).";
    $msg .= "\n\n(Erro na rede [errno $errno]: $message)";

    return new NFeAuthenticationException( $msg );
  }

  /**
   * NFe.io Certification Bundle
   */
  private static function caBundle() {
    return dirname(__FILE__) . '/../../data/ca-bundle.crt';
  }
}
