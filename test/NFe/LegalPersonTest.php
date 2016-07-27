<?php

class NFe_LegalPersonTest extends NFe_TestCase {
  private static $company_id = '53f0d6b690c14737349bd29c';

  public function testFetchPerson() {
    $result = NFe_LegalPerson::fetch( self::$company_id, '5775013f56c8e806dc72ad5e' );

    $this->assertNotNull($result);
  }

  public function testFetchFail() {
    $this->expectException('NFeObjectNotFound');

    $result = NFe_LegalPerson::fetch( self::$company_id, self::$company_id );

    $this->assertNull($result);
  }

  public function testSearch() {
    $persons = NFe_LegalPerson::search( self::$company_id );

    $this->assertTrue( count( $persons ) >= 1 );
  }
}
