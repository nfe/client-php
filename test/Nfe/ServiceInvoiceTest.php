<?php

class Nfe_ServiceInvoiceTest extends Nfe_TestCase
{

  public function testIssue()
  {
    $this->invoice = Nfe_ServiceInvoice::create(
      // ID da empresa, você deve copiar exatamente como está no painel
      "54244e0ee340420fdc94ad09",

      // Dados da nota fiscal de serviço
      Array (
        // Código do serviço de acordo com o a cidade
        'cityServiceCode' => '2690',
        // Descrição dos serviços prestados
        'description' => 'TESTE EMISSAO',
        // Valor total do serviços
        'servicesAmount' => 0.01,

        // Dados do Tomador dos Serviços
        'borrower' => Array(
          // CNPJ ou CPF (opcional para tomadores no exterior)
          'federalTaxNumber' => 191,
          // Nome da pessoa física ou Razão Social da Empresa
          'name' => 'BANCO DO BRASIL SA',
          // Email para onde deverá ser enviado a nota fiscal
          'email' => 'hackers@nfe.io',
          // Endereço do tomador
          'address' => Array(
            // Código do pais com três letras
            'country' => 'BRA',
            // CEP do endereço (opcional para tomadores no exterior)
            'postalCode' => '70073901',
            // Logradouro
            'street' => 'Outros Quadra 1 Bloco G Lote 32',
            // Número (opcional)
            'number' => 'S/N',
            // Complemento (opcional)
            'additionalInformation' => 'QUADRA 01 BLOCO G',
            // Bairro
            'district' => 'Asa Sul',
            // Cidade é opcional para tomadores no exterior
            'city' => Array(
                // Código do IBGE para a Cidade
                'code' => '5300108',
                // Nome da Cidade
                'name' => 'Brasilia'
            ),
            // Sigla do estado (opcional para tomadores no exterior)
            'state' => 'DF'
          )
        )
      )
    );

    $this->assertNotNull($this->invoice);
    $this->assertNotNull($this->invoice->id);
    $this->assertEqual($this->invoice->servicesAmount, 0.01);
    $this->assertEqual($this->invoice->cityServiceCode, '2690');
  }

  public function testFetchInvoice()
  {
    $fetched_invoice = Nfe_ServiceInvoice::fetch(
      "54244e0ee340420fdc94ad09",
      $this->invoice->id
    );

    $this->assertNotNull( $fetched_invoice );
    $this->assertNotNull( $fetched_invoice->id );
    $this->assertNotNull( $fetched_invoice->borrower );
    $this->assertEqual( $fetched_invoice->borrower->name, "BANCO DO BRASIL SA" );
  }

  public function testCancelInvoice()
  {
    $fetched_invoice = Nfe_ServiceInvoice::fetch(
      "54244e0ee340420fdc94ad09",
      $this->invoice->id
    );

    $this->assertNotNull($fetched_invoice);
    $this->assertNotNull($fetched_invoice->id);
    $this->assertEqual($fetched_invoice->id, $this->invoice->id);

    // cancel invoice
    $this->assertTrue($fetched_invoice->cancel());
  }

  public function testIssue_country_bad_request()
  {
    $this->invoice = Nfe_ServiceInvoice::create(
      // ID da empresa, você deve copiar exatamente como está no painel
      "568c0bceee083e5d453121bb",

      // Dados da nota fiscal de serviço
      Array (
        // Código do serviço de acordo com o a cidade
        'cityServiceCode' => '2690',
        // Descrição dos serviços prestados
        'description' => 'TESTE EMISSAO',
        // Valor total do serviços
        'servicesAmount' => 0.01,

        // Dados do Tomador dos Serviços
        'borrower' => Array(
          // CNPJ ou CPF (opcional para tomadores no exterior)
          'federalTaxNumber' => 191,
          // Nome da pessoa física ou Razão Social da Empresa
          'name' => 'BANCO DO BRASIL SA',
          // Email para onde deverá ser enviado a nota fiscal
          'email' => 'hackers@nfe.io',
          // Endereço do tomador
          'address' => Array(
            // Código do pais com três letras
            'country' => 'BRASIL',
            // CEP do endereço (opcional para tomadores no exterior)
            'postalCode' => '70073901',
            // Logradouro
            'street' => 'Outros Quadra 1 Bloco G Lote 32',
            // Número (opcional)
            'number' => 'S/N',
            // Complemento (opcional)
            'additionalInformation' => 'QUADRA 01 BLOCO G',
            // Bairro
            'district' => 'Asa Sul',
            // Cidade é opcional para tomadores no exterior
            'city' => Array(
                // Código do IBGE para a Cidade
                'code' => '5300108',
                // Nome da Cidade
                'name' => 'Brasilia'
            ),
            // Sigla do estado (opcional para tomadores no exterior)
            'state' => 'DF'
          )
        )
      )
    );

    $this->assertNull($this->invoice);
  }

}

?>
