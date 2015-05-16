<?php

class Nfe_Utilities {

  public static function authFromEnv() {
    $apiKey = getenv('NFE_API_KEY');
    if ($apiKey) {
      Nfe::setApiKey($apiKey);
    }
  }

  public static function utf8($value)
  {
    return (is_string($value) && mb_detect_encoding($value, "UTF-8", true) != "UTF-8")?utf8_encode($value):$value;
  }

  public static function convertDateFromISO( $datetime )
  {
    return strtotime($datetime);
  }

  public static function convertEpochToISO( $epoch ) {
    return date("c", $epoch);
  }

  public static function arrayToParams($array,$prefix=null) {
    if (!is_array($array)) return $array;

    return json_encode($array);
  }

  public static function arrayToParamsUrl($array,$prefix=null) {
    if (!is_array($array)) return $array;

    $params = Array();

    foreach ($array as $k => $v) {
      if (is_null($v)) continue;

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

    print_r($params);

    $v = implode("&", $params);

    //Encode the array into JSON.
    $jsonDataEncoded = json_encode($jsonData);

    return $v;
  }



}
