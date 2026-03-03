<?php

class NFe_Webhook extends NFe_APIResource {

  /**
   * Webhook API base URL (api.nfse.io/v2).
   * Webhooks use a different API host and version than other resources.
   */
  private static $webhookBaseURI = 'https://api.nfse.io/v2';

  /**
   * Response wrapper key used by the v2 API.
   * The v2 API returns {"webHook": {...}} (camelCase, singular).
   */
  private static $responseKey = 'webHook';

  /**
   * Override the endpoint to use api.nfse.io/v2/webhooks instead of api.nfe.io/v1/hooks.
   *
   * @param mixed  $object   Webhook ID (string) or null
   * @param string $uri_path Not used for webhooks
   * @return string The full endpoint URL
   */
  public static function endpointAPI( $object = null, $uri_path = '' ) {
    $path = '';

    if ( is_string($object) || is_integer($object) ) {
      $path = '/' . $object;
    }

    if ( is_array($object) && isset($object['id']) ) {
      $path = '/' . $object['id'];
    }

    return strtolower( self::$webhookBaseURI . '/' . self::objectBaseURI() . $path );
  }

  /**
   * Extract webhook data from v2 API response.
   * The v2 API wraps data in {"webHook": {...}} instead of {"webhooks": {...}}.
   *
   * @param object $response Raw API response
   * @return object The webhook data object
   */
  private static function extractFromResponse( $response ) {
    $key = self::$responseKey;
    if ( is_object($response) && isset($response->$key) ) {
      return $response->$key;
    }
    return $response;
  }

  public static function create( $attributes = array() ) {
    $response = self::API()->request( 'POST', self::endpointAPI( $attributes ), $attributes );
    return self::createFromResponse( self::extractFromResponse($response) );
  }

  public static function fetch( $key ) {
    try {
      $response = self::API()->request( 'GET', static::endpointAPI($key) );
      return self::createFromResponse( self::extractFromResponse($response) );
    }
    catch ( NFeObjectNotFound $e ) {
      throw new NFeObjectNotFound( self::convertClassToObjectType() . ': não encontrado.');
    }
  }

  public static function search( $options = array() ) {
    try {
      $response = self::API()->request( 'GET', static::endpointAPI($options), $options );
      return self::createFromResponse( self::extractFromResponse($response) );
    }
    catch (Exception $e) {}
    return array();
  }

  public function save() {
    try {
      $response = self::API()->request( $this->is_new() ? 'POST' : 'PUT', static::endpointAPI($this), $this->getAttributes() );
      $new_object = self::createFromResponse( self::extractFromResponse($response) );

      $this->copy( $new_object );
      $this->resetStates();

      if ( isset($response->errors) ) {
        throw new NFeException();
      }
    }
    catch (Exception $e) {
      return false;
    }
    return true;
  }

  public function delete() {
    try {
      $response = self::API()->request( 'DELETE', static::endpointAPI($this) );
      if ( isset($response->errors) ) {
        throw new NFeException();
      }
    }
    catch (Exception $e) {
      return false;
    }
    return true;
  }

  public function refresh() {
    if ( $this->is_new() ) {
      return false;
    }

    try {
      $response = self::API()->request( 'GET', static::endpointAPI($this) );
      if ( isset($response->errors) ) {
        throw new NFeObjectNotFound();
      }

      $new_object = self::createFromResponse( self::extractFromResponse($response) );
      $this->copy( $new_object );
      $this->resetStates();
    }
    catch (Exception $e) {
      return false;
    }
    return true;
  }
}
