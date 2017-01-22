<?php

abstract class NFe_io {

  // @var string The NFe API key to be used for requests.
  public static $api_key = null;

  // @var string The version of the NFe API to use for requests.
  public static $api_version = 'v1';

  // @var string The base URL for the NFe API.
  public static $endpoint = 'https://api.nfe.io';

  // @var boolean Defaults to true.
  public static $verifySslCerts = true;

  // @var boolean Defaults to false
  public static $debug = false;

  const VERSION = '2.0.0';

  /**
   * Sets the Base URI to be sued for requests
   *
   * @return string
   */
  public static function getBaseURI() {
    return self::$endpoint . '/' . self::$api_version;
  }

  /**
   * Sets the Host to be used for requests
   *
   * @param string $host Base URI
   */
  public static function setHost( $host ) {
    self::$endpoint = $host;
  }

  /**
   * Sets the API key to be used for requests.
   *
   * @param string $apiKey
   */
  public static function setApiKey( $_api_key ) {
    self::$api_key = $_api_key;
  }

  /**
  * @return string The API key used for requests.
  */
  public static function getApiKey() {
    return self::$api_key;
  }

  /**
   * @return boolean
   */
  public static function getVerifySslCerts() {
      return self::$verifySslCerts;
  }
}
