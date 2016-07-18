<?php

class NFe_LegalPerson extends NFe_APIResource {
  public static function fetch( $companyId, $id ) {
    return self::fetchAPI( array( 'company_id' => $companyId, 'id' => $id ) );
  }

  public static function search( $companyId ) {
    return self::searchAPI( array( 'company_id' => $companyId ) );
  }
}
