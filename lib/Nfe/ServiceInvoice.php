<?php

class NFe_ServiceInvoice extends NFe_APIResource {
  
  public static function create( $companyId, $attributes = array() ) {
    $attributes['company_id'] = $companyId;
    
    return self::createAPI($attributes);
  }

  public static function fetch( $companyId, $id ) {
    return self::fetchAPI( array( 'company_id' => $companyId, 'id' => $id ) );
  }

  public static function pdf( $companyId, $id ) {
    try {
      $url      = static::url( array( 'company_id' => $companyId, 'id' => $id ) ) . '/pdf';
      $response = self::API()->request( 'GET', $url );

      return $response;
    } 
    catch (Exception $e) {
      return false;
    }
  }

  public static function xml( $companyId, $id ) {
    try {
      $url      = static::url( array( 'company_id' => $companyId, 'id' => $id ) ) . '/xml';
      $response = self::API()->request( 'GET', $url );
      
      return $response;
    } 
    catch (Exception $e) {
      return false;
    }
  }

  public function cancel() {
    return $this->deleteAPI();
  }
}
