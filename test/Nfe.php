<?php

include_once( dirname(__FILE__) . '/../vendor/simpletest/simpletest/autorun.php');

error_reporting( E_ALL | E_STRICT );

echo 'Running NFe.io PHP Test Suite\r\n';

include_once(dirname(__FILE__) . '/../lib/init.php');
include_once(dirname(__FILE__) . '/NFe/TestCase.php');
include_once(dirname(__FILE__) . '/NFe/CompanyTest.php');
// include_once(dirname(__FILE__).'/Nfe/LegalPersonTest.php');
// include_once(dirname(__FILE__).'/Nfe/NaturalPersonTest.php');
// include_once(dirname(__FILE__).'/Nfe/WebhookTest.php');
// include_once(dirname(__FILE__) . '/NFe/ServiceInvoiceTest.php');
