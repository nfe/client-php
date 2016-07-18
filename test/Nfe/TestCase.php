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
}
