<?php

$dir = dirname(__FILE__);

// NFe Base
require( $dir . '/NFe/NFe.php' );

// NFe Errors
require( $dir . '/NFe/Error.php' );

// NFe Utilities
require( $dir . '/NFe/Backward_Compatibility.php' );
require( $dir . '/NFe/Utilities.php' );

// NFe API Plumbing
require( $dir . '/NFe/Object.php' );
require( $dir . '/NFe/APIRequest.php' );
require( $dir . '/NFe/APIResource.php' );
require( $dir . '/NFe/APIChildResource.php' );

// NFe API Resources
require( $dir . '/NFe/Company.php' );
require( $dir . '/NFe/LegalPerson.php' );
require( $dir . '/NFe/NaturalPerson.php' );
require( $dir . '/NFe/ServiceInvoice.php' );
require( $dir . '/NFe/SearchResult.php' );
require( $dir . '/NFe/Webhook.php' );
