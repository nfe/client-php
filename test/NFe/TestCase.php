<?php

class NFe_TestCase extends UnitTestCase {
  public function __construct() {
    $apiKey = getenv('NFE_API_KEY');
    NFe_io::setApiKey($apiKey);
  }
}
