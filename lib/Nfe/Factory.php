<?php

class Nfe_Factory {
  public static function createFromResponse( $object_type, $response ) {
    // Should i send fetch to here?
    $object_type = str_replace(" ", "", ucwords(str_replace("_", " ", $object_type)));
    $class_name = "Nfe_" . $object_type;

    if ( ! class_exists($class_name) ) {
      return null;
    }

    if ( is_object($response) && (isset($response->items)) && (isset($response->totalItems)) ) {
      $results = array();

      foreach ($response->items as $item) {
        array_push( $results, self::createFromResponse($object_type, $item) );
      }

      return new Nfe_SearchResult( $results, $response->totalItems );
    }  elseif (is_array($response)) {
      $results = array();

      foreach ($response as $item) {
        array_push( $results, self::createFromResponse($object_type, $item) );
      }

      return new Nfe_SearchResult( $results, count($results) );
    }
    elseif (is_object($response)) {
      return new $class_name( (array) $response );
    }

    return null;
  }
}
