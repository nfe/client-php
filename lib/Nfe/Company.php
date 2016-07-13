<?php

class Nfe_Company extends Nfe_APIResource {

  public static function create($attributes=Array()) {
    return self::createAPI($attributes);
  }
  public static function fetch($key)                 { return self::fetchAPI($key); }
  public        function save()                      { return $this->saveAPI(); }
  public        function delete()                    { return $this->deleteAPI(); }

  public        function refresh()                   { return $this->refreshAPI(); }
  public static function search($options=Array())    { return self::searchAPI($options); }

}
