<?php

class NfeAuthenticationException extends Exception {}
class NfeRequestException extends Exception {}
class NfeObjectNotFound extends Exception {}
class NfeException extends Exception {}

abstract class NfeResource {
}

abstract class Nfe {
  const VERSION = "1.0.0";

  public static $api_key = null;
  public static $api_version = "v1";
  public static $endpoint = "https://api.nfe.io";

  public static function getBaseURI() {
   return self::$endpoint . "/" . self::$api_version;
  }

  public static function setHost( $host ) {
    self::$endpoint = $host;
  }

  public static function setApiKey( $_api_key ) {
    self::$api_key = $_api_key;
  }

  public static function getApiKey() {
    return self::$api_key;
  }
}
