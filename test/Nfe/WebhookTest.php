<?php

class NFe_WebhookTest extends NFe_TestCase {
  private static $id = null;

  public function testCreateAndDelete() {
    $attributes = array(
      'url'    => 'http://google.com/test',
      'events' => array(
        'issue',
        'cancel'
      ),
      'status' => 'active'
    );

    $object = NFe_Webhook::create( $attributes );

    $this->assertNotNull($object);
    $this->assertNotNull($object->hooks->url);
    $this->assertEqual($object->hooks->url, $attributes['url']);

    self::$id = $object->hooks->id;
  }

  public function testGet() {
    $object = NFe_Webhook::fetch( self::$id );

    $this->assertNotNull( $object );
    $this->assertNotNull( $object['hooks'][0]->url );
    $this->assertEqual( $object['hooks'][0]->url, 'http://google.com/test' );
  }

  public function testUpdate() {
    $object = NFe_Webhook::fetch( self::$id );

    $new_url = 'http://google.com/test2';
    $object->hooks->url = $new_url;

    $this->assertTrue($object->save());
    $this->assertNotNull($object);
    $this->assertNotNull($object->hooks->url);
    $this->assertEqual($object->hooks->url, $new_url);
  }

  public function testDelete() {
    $object = NFe_Webhook::fetch( self::$id );

    $this->assertNotNull($object);
    $this->assertNotNull( $object->hooks->url );
    $this->assertTrue($object->delete());
  }

  public function testSearch() {
    $hooks = NFe_Webhook::search();

    $this->assertNotNull( $hooks );
    $this->assertNotNull( $hooks->hooks );
  }
}
