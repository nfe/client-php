<?php

abstract class NFe {
  const VERSION = '2.0.0';

  // @var string The NFe API key to be used for requests.
  public static $api_key = null;

  // @var string|null The version of the NFe API to use for requests.
  public static $api_version = 'v1';

  // @var string The base URL for the NFe API.
  public static $endpoint = 'https://api.nfe.io';

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
}
