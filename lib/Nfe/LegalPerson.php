<?php

class Nfe_LegalPerson extends Nfe_APIResource {

  public static function fetch($companyId, $id) {
    return self::fetchAPI(Array(
      "company_id" => $companyId,
      "id" => $id
    ));
  }

  public static function search($companyId) {
    return self::searchAPI(Array(
      "company_id" => $companyId
    ));
  }

}
