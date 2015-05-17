<?php

class Nfe_ServiceInvoice extends APIResource {
  public static function create($companyId, $attributes=Array()) {
    $attributes["company_id"] = $companyId;
    return self::createAPI($attributes);
  }
  public static function fetch($key)                  { return self::fetchAPI($key); }
  public        function delete()                    { return $this->deleteAPI(); }

  public        function refresh()                   { return $this->refreshAPI(); }
  public static function search($options=Array())    { return self::searchAPI($options); }

  public function pdf() {
    if ($this->is_new()) return false;

    try {
      $response = self::API()->request(
        "GET",
        static::url($this) . "/pdf"
      );
      if (isset($response->errors)) throw NfeRequestException( $response->errors );
      $new_object = self::createFromResponse( $response );
      $this->copy( $new_object );
      $this->resetStates();
    } catch (Exception $e) {
      return false;
    }

    return true;
  }

  public function refund() {
    if ($this->is_new()) return false;

    try {
      $response = self::API()->request(
        "POST",
        static::url($this) . "/refund"
      );
      if (isset($response->errors)) {
        throw IuguRequestException( $response->errors );
      }
      $new_object = self::createFromResponse( $response );
      $this->copy( $new_object );
      $this->resetStates();
    } catch (Exception $e) {
      return false;
    }

    return true;
  }
}
