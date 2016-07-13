<?php

class Nfe_APIResource extends Nfe_Object
{
  private static $_apiRequester = null;

  public static function convertClassToObjectType() {
    $object_type = str_replace("Nfe_", "", get_called_class());
    $object_type = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $object_type));
    return mb_strtolower($object_type, "UTF-8");
  }

  public static function objectBaseURI() {
    $object_type = self::convertClassToObjectType();
    switch($object_type) {
      // Add Exceptions as needed
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
    if (Nfe_APIResource::$_apiRequester == null) Nfe_APIResource::$_apiRequester = new Nfe_APIRequest();
    return Nfe_APIResource::$_apiRequester;
  }

  public static function endpointAPI($object=NULL, $uri_path="") {

    $path = "";

    if (is_string($object) || is_integer($object)) {
      $path = "/" . $object;
    }
    else if (is_array($object) && isset($object["company_id"])) {
      $uri_path = "/companies/" . $object["company_id"];
    }
    else if (is_object($object) && isset($object->provider) && isset($object->provider->id)) {
      $uri_path = "/companies/" . $object->provider->id;
    }

    if (isset($object["id"])) {
      $path = "/" . $object["id"];
    }

    $ret = strtolower(Nfe::getBaseURI() . $uri_path . "/" . self::objectBaseURI() . $path);

    return $ret;
  }

  public static function url($object=NULL) {
    return self::endpointAPI( $object );
  }

  protected static function createFromResponse($response) {
    return Nfe_Factory::createFromResponse(
      self::convertClassToObjectType(),
      $response
    );
  }

  protected static function createAPI($attributes=Array()) {
    return self::createFromResponse(
      self::API()->request(
        "POST",
        self::endpointAPI( $attributes ),
        $attributes
      )
    );
  }

  protected function deleteAPI() {
    if ($this["id"] == null) return false;

    try {
      $response = self::API()->request(
        "DELETE",
        static::url($this)
      );

      if (isset($response->errors)) throw NfeException();
    } catch (Exception $e) {
      return false;
    }

    return true;
  }

  protected static function searchAPI($options=Array()) {
    try {
      $response = self::API()->request(
        "GET",
        static::url($options),
        $options
      );

      return self::createFromResponse($response);
    } catch (Exception $e) {}

    return Array();
  }

  protected static function fetchAPI($key) {
    try {

      $response = self::API()->request(
        "GET",
        static::url($key)
      );

      return self::createFromResponse($response);
    } catch (NfeObjectNotFound $e) {
      throw new NfeObjectNotFound(self::convertClassToObjectType(get_called_class()) . ":" . " not found");
    }
  }

  protected function refreshAPI() {
    if ($this->is_new()) return false;

    try {
      $response = self::API()->request(
        "GET",
        static::url($this)
      );

      if (isset($response->errors)) throw NfeObjectNotFound();

      $type = self::objectBaseURI();
      $new_object = self::createFromResponse($response->$type);
      $this->copy( $new_object );
      $this->resetStates();

    } catch (Exception $e) {
      return false;
    }

    return true;
  }

  protected function saveAPI() {
    try {
      $response = self::API()->request(
        $this->is_new() ? "POST" : "PUT",
        static::url($this),
        $this->getAttributes()
      );

      $type = self::objectBaseURI();
      $new_object = self::createFromResponse($response->$type);
      $this->copy( $new_object );
      $this->resetStates();

      if (isset($response->errors)) throw new NfeException();

    } catch (Exception $e) {
      return false;
    }

    return true;
  }
}
