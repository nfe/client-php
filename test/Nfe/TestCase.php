<?php

class NFe_TestCase extends UnitTestCase {

  function __construct() {
    $apiKey = getenv('NFE_API_KEY');
    NFe::setApiKey($apiKey);
  }

  protected static function getOrCreateTestCompany( $_attributes = array() ) {
    $attributes = array(
      "federalTaxNumber" => 191,
      "name"             => "BANCO DO BRASIL SA",
      "tradeName"        => "BANCO DO BRASIL",
      "email"            => "exemplo@bb.com.br"
    );

    $object = NFe_Company::fetch( (string) $attributes["federalTaxNumber"] );

    if (is_null($object)) {
      $object = NFe_Company::create( array_merge($attributes,$_attributes) );
    }

    return $object;
  }
}
