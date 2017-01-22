<?php

class NFe_APIResource extends NFe_Object {
  private static $_apiRequester = null;

  public static function convertClassToObjectType() {
    $object_type = str_replace('NFe_', '', get_called_class());
    $object_type = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $object_type));

    return strtolower($object_type);
  }

  public static function objectBaseURI() {
    $object_type = self::convertClassToObjectType();

    switch($object_type) { // Add Exceptions as needed
      case 'company':
        return 'companies';
      case 'legal_person':
        return 'legalPeople';
      case 'natural_person':
        return 'naturalPeople';
      case 'service_invoice':
        return 'serviceInvoices';
      case 'webhook':
        return 'hooks';
      default:
       return $object_type . 's';
    }
  }

  public static function API() {
    if ( NFe_APIResource::$_apiRequester == null ) {
      NFe_APIResource::$_apiRequester = new NFe_APIRequest();
    }

    return NFe_APIResource::$_apiRequester;
  }

  public static function endpointAPI( $object = null, $uri_path = '' ) {
    $path = '';

    if ( is_string($object) || is_integer($object) ) {
      $path = '/' . $object;
    }
    elseif (is_array($object) && isset($object['company_id'])) {
      $uri_path = '/companies/' . $object['company_id'];
    }
    elseif (is_object($object) && isset($object->provider) && isset($object->provider->id) ) {
      $uri_path = '/companies/' . $object->provider->id;
    }

    if (isset($object['id'])) {
      $path = '/' . $object['id'];
    }

    return strtolower( NFe_io::getBaseURI() . $uri_path . '/' . self::objectBaseURI() . $path );
  }

  public static function url( $object = null ) {
    return self::endpointAPI( $object );
  }

  protected static function createFromResponse($response) {
    return NFe_Utilities::createFromResponse( self::convertClassToObjectType(), $response );
  }

  protected static function createAPI( $attributes = array() ) {
    return self::createFromResponse(
      self::API()->request( 'POST', self::endpointAPI( $attributes ), $attributes ) );
  }

  protected function deleteAPI() {
    // if ( $this['id'] == null ) { // $this['id']
      // return false;
    // }

    try {
      $response = self::API()->request( 'DELETE', static::url($this) );

      if ( isset($response->errors) ) {
        throw NFeException();
      }
    }
    catch (Exception $e) {
      return false;
    }

    return true;
  }

  protected static function searchAPI( $options = array() ) {
    try {
      $response = self::API()->request( 'GET', static::url($options), $options );

      return self::createFromResponse($response);
    }
    catch (Exception $e) {}

    return array();
  }

  protected static function fetchAPI($key) {
    try {
      $response = self::API()->request( 'GET', static::url($key) );
      return self::createFromResponse($response);
    }
    catch ( NFeObjectNotFound $e ) {
      throw new NFeObjectNotFound( self::convertClassToObjectType( get_called_class() ) . ':' . ' não encontrado.');
    }
  }

  protected function refreshAPI() {
    if ( $this->is_new() ) {
      return false;
    }

    try {
      $response = self::API()->request( 'GET', static::url($this) );

      if ( isset($response->errors) ) {
        throw NFeObjectNotFound();
      }

      $type       = self::objectBaseURI();
      $new_object = self::createFromResponse($response->$type);
      $this->copy( $new_object );
      $this->resetStates();
    }
    catch (Exception $e) {
      return false;
    }

    return true;
  }

  protected function saveAPI() {
    try {
      $response   = self::API()->request( $this->is_new() ? 'POST' : 'PUT', static::url($this), $this->getAttributes() );
      $type       = self::objectBaseURI();
      $new_object = self::createFromResponse($response->$type);

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
}
