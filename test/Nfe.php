<?php

include_once(dirname(__FILE__)."/../vendor/simpletest/simpletest/autorun.php");

error_reporting( E_ALL | E_STRICT );

echo "Running NFe.io PHP Test Suite\r\n";

if (!getenv('NFE_API_KEY')) {
  echo "MISSING NFE_API_KEY in Environment.\n You should try 'export NFE_API_KEY=<your_api_key>'";
  exit(1);
}

include_once(dirname(__FILE__)."/../lib/Nfe.php");

include_once(dirname(__FILE__)."/Nfe/TestCase.php");
include_once(dirname(__FILE__)."/Nfe/CompanyTest.php");
include_once(dirname(__FILE__)."/Nfe/LegalPersonTest.php");
include_once(dirname(__FILE__)."/Nfe/NaturalPersonTest.php");
include_once(dirname(__FILE__)."/Nfe/WebhookTest.php");

?>
