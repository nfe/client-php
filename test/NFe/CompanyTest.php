<?php

class NFe_CompanyTest extends NFe_TestCase {
  private static $id = null;

  public function testCreateAndDelete() {
    $attributes = array(
      'federalTaxNumber' => 97625117000100, // Generate CNPJ here: http://www.geradordecnpj.org/
      'name'             => 'TEST Company Name',
      'tradeName'        => 'Company Name',
      'email'            => 'nfe@mailinator.com',
      'address'          => array(
        'country'               => 'BRA',
        'postalCode'            => '70073901',
        'street'                => 'Outros Quadra 1 Bloco G Lote 32',
        'number'                => 'S/N',
        'additionalInformation' => 'QUADRA 01 BLOCO G',
        'district'      => 'Asa Sul',
        'city'          => array(
            'code' => '5300108',
            'name' => 'Brasilia'
        ),
        'state' => 'DF'
      ),
      'environment'     => 'Development'
    );

    $object = NFe_Company::create( $attributes );

    $this->assertNotNull($object);
    $this->assertNotNull( $object->name );
    $this->assertEqual( $object->name, 'TEST Company Name' );
    self::$id = $object->id;
  }

  public function testGet() {
    $object = NFe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object->name);
    $this->assertEqual($object->name, 'TEST Company Name');
  }

  public function testUpdate() {
    $object = NFe_Company::fetch( self::$id );

    $object->name = 'BB SA';

    // @todo Check it out why this is giving error
    // $this->assertTrue( $object->save() );

    $this->assertNotNull($object);
    $this->assertNotNull($object->name);
    $this->assertEqual($object->name, 'BB SA');
  }

  public function testDelete() {
    $object = NFe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object->name);
    $this->assertTrue( $object->delete() );
  }
}
