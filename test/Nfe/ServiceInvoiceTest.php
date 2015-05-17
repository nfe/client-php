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

  }

  // public function testCreateInvoice()
  // {
  //   $this->assertNotNull($this->invoice);
  //   $this->assertTrue( count($this->invoice["errors"]) == 0 );
  // }
  //
  // public function testCreateEmptyInvoice()
  // {
  //   $invoice = Iugu_Invoice::create();
  //   $this->assertNotNull( $invoice );
  //   $this->assertTrue( count($invoice["errors"]) > 0 );
  // }
  //
  // public function testRefreshInvoice()
  // {
  //   $this->assertTrue( $this->invoice->refresh() );
  // }
  //
  // public function testFetchInvoice()
  // {
  //   $this->expectException("IuguException");
  //   $new_invoice = Iugu_Invoice::fetch( "NO VALID INVOICE" );
  //
  //   $fetched_invoice = Iugu_Invoice::fetch( $this->invoice->id );
  //   $this->assertNotNull( $fetched_invoice );
  //   $this->assertNotNull( $fetched_invoice["id"] );
  // }

}

?>
