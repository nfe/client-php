<?php

class NFe_Company extends NFe_APIResource {
  public static function create( $attributes = array() ) {
    return self::createAPI($attributes);
  }

  public static function fetch($key) {
  	return self::fetchAPI($key);
  }

  public function save() {
  	return $this->saveAPI();
  }

  public function delete() {
  	return $this->deleteAPI();
  }

  public function refresh() {
  	return $this->refreshAPI();
  }

  public static function search( $options = array() ) {
  	return self::searchAPI($options);
  }
}
