<?php

// NFe Base
require( dirname(__FILE__) . '/NFe/NFe.php' );

// NFe Utilities
require( dirname(__FILE__) . '/NFe/Backward_Compatibility.php' );
require( dirname(__FILE__) . '/NFe/Utilities.php' );

// NFe API Plumbing
require( dirname(__FILE__) . '/NFe/APIRequest.php' );
require( dirname(__FILE__) . '/NFe/APIResource.php' );
require( dirname(__FILE__) . '/NFe/APIChildResource.php' );
require( dirname(__FILE__) . '/NFe/Object.php' );

// NFe Errors
require( dirname(__FILE__) . '/NFe/Error.php' );

// NFe API Resources
require( dirname(__FILE__) . '/NFe/Company.php' );
require( dirname(__FILE__) . '/NFe/LegalPerson.php' );
require( dirname(__FILE__) . '/NFe/NaturalPerson.php' );
require( dirname(__FILE__) . '/NFe/ServiceInvoice.php' );
require( dirname(__FILE__) . '/NFe/SearchResult.php' );
require( dirname(__FILE__) . '/NFe/Webhook.php' );
