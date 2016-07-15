<?php

class NFe_TestCase extends UnitTestCase {

  const API_KEY = 'yucG1xbixd2SxjihAwmTKnx7z9pmD9NwfMYxzCVWDx3G999NAkC6LpBnqqWQoXScMWl';

  public function __construct() {
    $apiKey = getenv('NFE_API_KEY');
    if ( ! $apiKey ) {
      $apiKey = self::API_KEY;
    }
    NFe::setApiKey($apiKey);
  }

  /* protected static function getOrCreateTestCompany( $_attributes = array() ) {
    $attributes = array(
      'federalTaxNumber' => 87502637000164, // Generate CNPJ here: http://www.geradordecnpj.org/
      'name'             => 'TEST BANCO DO BRASIL SA',
      'tradeName'        => 'BANCO DO BRASIL',
      'email'            => 'nfe@mailinator.com'
    );

    $object = NFe_Company::fetch( (string) $attributes['federalTaxNumber'] );

    if ( is_null($object) ) {
      $object = NFe_Company::create( array_merge($attributes,$_attributes) );
    }

    return $object;
  } */
}
