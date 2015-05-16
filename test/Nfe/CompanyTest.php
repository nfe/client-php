<?php

class Nfe_CompanyTest extends Nfe_TestCase
{
  private static $id = null;

  public function testCreateAndDelete()
  {
    $attributes = Array(
      "federalTaxNumber" => 191,
      "name" => "BANCO DO BRASIL SA",
      "tradeName" => "BANCO DO BRASIL",
      "email" => "exemplo@bb.com.br"
    );

    $object = Nfe_Company::create( $attributes );

    $this->assertNotNull($object);
    $this->assertNotNull($object["name"]);
    $this->assertEqual($object["name"], "BANCO DO BRASIL SA");

    self::$id = $object["id"];
  }

  public function testGet()
  {
    $object = Nfe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object["name"]);
    $this->assertEqual($object["name"], "BANCO DO BRASIL SA");
  }

  public function testUpdate()
  {
    $object = Nfe_Company::fetch( self::$id );

    $object["name"] = "BB SA";

    $this->assertTrue($object->save());
    $this->assertNotNull($object);
    $this->assertNotNull($object["name"]);
    $this->assertEqual($object["name"], "BB SA");
  }

  public function testDelete()
  {
    $object = Nfe_Company::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object["name"]);
    $this->assertTrue($object->delete());
  }
}

?>
