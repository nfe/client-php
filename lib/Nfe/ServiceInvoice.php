<?php

class Nfe_ServiceInvoice extends APIResource {
  public static function create($companyId, $attributes=Array()) {
    $attributes["company_id"] = $companyId;
    return self::createAPI($attributes);
  }

  public static function fetch($companyId, $id) {
    return self::fetchAPI(Array(
      "company_id" => $companyId,
      "id" => $id
    ));
  }

  public function cancel() {
    return $this->deleteAPI();
  }
}
