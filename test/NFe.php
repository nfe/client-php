<?php

include_once( dirname(__FILE__) . '/../vendor/simpletest/simpletest/autorun.php');

error_reporting( E_ALL | E_STRICT );

echo 'Running NFe.io PHP Test Suite - ';

include_once(dirname(__FILE__) . '/../lib/init.php');
include_once(dirname(__FILE__) . '/NFe/TestCase.php');
include_once(dirname(__FILE__) . '/NFe/CompanyTest.php');
include_once(dirname(__FILE__) . '/NFe/LegalPersonTest.php');
include_once(dirname(__FILE__) . '/NFe/NaturalPersonTest.php');
include_once(dirname(__FILE__) . '/NFe/WebhookTest.php');
include_once(dirname(__FILE__) . '/NFe/ServiceInvoiceTest.php');
