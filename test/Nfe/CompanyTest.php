<?php

class NFe_CompanyTest extends NFe_TestCase {
  private static $id = null;

  public function testCreateAndDelete() {
    $attributes = array(
      'federalTaxNumber' => 87502637000164, // Generate CNPJ here: http://www.geradordecnpj.org/
      'name'             => 'TEST BANCO DO BRASIL SA',
      'tradeName'        => 'BANCO DO BRASIL',
      'email'            => 'nfe@mailinator.com',
      'address'          => array(
        'country'               => 'BRA',
        'postalCode'            => '70073901',
        'street'                => 'Outros Quadra 1 Bloco G Lote 32',
        'number'                => 'S/N',
        'additionalInformation' => 'QUADRA 01 BLOCO G',
        'district' => 'Asa Sul',
            'code' => '5300108',
            'name' => 'Brasilia'
        ),
        'state' => 'DF'
      )
    );

    $object = NFe_Company::create( $attributes );

    $this->assertNotNull($object);
    $this->assertNotNull($object['name']);
    $this->assertEqual($object['name'], 'TEST BANCO DO BRASIL SA');

    self::$id = $object['id'];
  }

  public function atestGet() {
    $object = NFe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object['name']);
    $this->assertEqual($object['name'], 'TEST BANCO DO BRASIL SA');
  }

  public function atestUpdate() {
    $object = NFe_Company::fetch( self::$id );

    $object['name'] = 'BB SA';

    $this->assertTrue($object->save());
    $this->assertNotNull($object);
    $this->assertNotNull($object['name']);
    $this->assertEqual($object['name'], 'BB SA');
  }

  public function atestDelete() {
    $object = NFe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object['name']);
    $this->assertTrue($object->delete());
  }
}
