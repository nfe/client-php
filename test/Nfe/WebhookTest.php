<?php

class Nfe_WebhookTest extends Nfe_TestCase
{
  private static $id = null;

  public function testCreateAndDelete()
  {
    $attributes = Array(
      "url" => "http://google.com/test",
      "events" => Array(
        "issue",
        "cancel"
      )
    );

    $object = Nfe_Webhook::create( $attributes );

    $this->assertNotNull($object);
    $this->assertNotNull($object["url"]);
    $this->assertEqual($object["url"], $attributes["url"]);

    self::$id = $object["id"];
  }

  public function testGet()
  {
    $object = Nfe_Webhook::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object["url"]);
    $this->assertEqual($object["url"], "http://google.com/test");
  }

  public function testUpdate()
  {
    $object = Nfe_Webhook::fetch( self::$id );

    $new_name = "http://google.com/test2";
    $object["url"] = $new_name;

    $this->assertTrue($object->save());
    $this->assertNotNull($object);
    $this->assertNotNull($object["url"]);
    $this->assertEqual($object["url"], $new_name);
  }

  public function testDelete()
  {
    $object = Nfe_Webhook::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull($object["url"]);
    $this->assertTrue($object->delete());
  }
}

?>
