<?php

class NFe_Utilities {

  public static function authFromEnv() {
    $apiKey = getenv('NFE_API_KEY');
    if ($apiKey) {
      NFe_io::setApiKey($apiKey);
    }
  }

  public static function utf8($value) {
    return (is_string($value) && mb_detect_encoding($value, "UTF-8", true) != "UTF-8") ? utf8_encode($value) : $value;
  }

  public static function convertDateFromISO( $datetime ) {
    return strtotime($datetime);
  }

  public static function convertEpochToISO( $epoch ) {
    return date("c", $epoch);
  }

  public static function arrayToParams($array, $prefix = null) {
    if ( !is_array($array) ) {
      return $array;
    }

    return json_encode($array);
  }

  public static function arrayToParamsUrl($array, $prefix = null) {
    if ( !is_array($array) ) {
      return $array;
    }

    $params = array();
    foreach ($array as $k => $v) {
      if ( is_null($v) ) {
        continue;
      }

      if ($prefix && $k && !is_int($k))
        $k = $prefix."[".$k."]";
      else if ($prefix)
        $k = $prefix."[]";

      if (is_array($v)) {
        $params[] = self::arrayToParams($v, $k);
      } else {
        $params[] = $k."=".urlencode($v);
      }
    }

    $v = implode("&", $params);

    // Encode the array into JSON.
    $jsonDataEncoded = json_encode($jsonData);

    return $v;
  }

  public static function createFromResponse( $object_type, $response ) {
    // Should i send fetch to here?
    $object_type = str_replace(" ", "", ucwords( str_replace("_", " ", $object_type) ) );
    $class_name = 'NFe_' . $object_type;

    // Bail if class doesn't exist
    if ( ! class_exists($class_name) ) {
      return null;
    }

    if ( is_object($response) && ( isset($response->items) ) && ( isset($response->totalItems) ) ) {
      $results = array();

      foreach ($response->items as $item) {
        array_push( $results, self::createFromResponse($object_type, $item) );
      }

      return new NFe_SearchResult( $results, $response->totalItems );
    }
    elseif ( is_array($response) ) {
      $results = array();

      foreach ($response as $item) {
        array_push( $results, self::createFromResponse($object_type, $item) );
      }

      return new NFe_SearchResult( $results, count($results) );
    }
    elseif ( is_object($response) ) {
      return new $class_name( (array) $response );
    }

    return null;
  }
}
