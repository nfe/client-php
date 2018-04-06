<?php

$dir = dirname(__FILE__);

include_once( $dir . '/../vendor/simpletest/simpletest/autorun.php');

error_reporting( E_ALL | E_STRICT );

echo 'Running NFe.io PHP Test Suite - ';

include_once( $dir . '/../lib/init.php');
include_once( $dir . '/NFe/TestCase.php');
include_once( $dir . '/NFe/CompanyTest.php');
include_once( $dir . '/NFe/LegalPersonTest.php');
include_once( $dir . '/NFe/NaturalPersonTest.php');
include_once( $dir . '/NFe/WebhookTest.php');
include_once( $dir . '/NFe/ServiceInvoiceTest.php');
