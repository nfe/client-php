<?php

class Nfe_LegalPersonTest extends Nfe_TestCase
{
  private static $companId = "53f0d6b690c14737349bd29c";

  public function testFetch()
  {
    $this->expectException("NfeObjectNotFound");

    $result = Nfe_LegalPerson::fetch( self::$companId, self::$companId );

    $this->assertNull($result);
  }

  public function testSearch()
  {
    $searchResults = Nfe_LegalPerson::search(self::$companId);

    $this->assertTrue( $searchResults->total() > 0 );
  }
}

?>
