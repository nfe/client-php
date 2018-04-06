# Cliente PHP da API do NFe.io

[![Build Status](https://travis-ci.org/nfe/client-php.svg?branch=master)](https://travis-ci.org/nfe/client-php)
[![Latest Stable Version](https://poser.pugx.org/nfe/nfe/v/stable)](https://packagist.org/packages/nfe/nfe)
[![Total Downloads](https://poser.pugx.org/nfe/nfe/downloads.svg)](https://packagist.org/packages/nfe/nfe)
[![License](https://poser.pugx.org/nfe/nfe/license.svg)](https://packagist.org/packages/nfe/nfe)

## Requisitos

* PHP 5.4 em diante.

## Instalação

### [Composer](http://getcomposer.org/) via [Packagist](packagist.org/packages/nfe/nfe)

  - Você pode instalar via [Composer](http://getcomposer.org/), executando o comando a seguir:

  ```bash
  composer require nfe/nfe
  ```

  - Para usar a biblioteca, use o [Composer autoload](https://getcomposer.org/doc/00-intro.md#autoloading):

  ```php
  require_once('vendor/autoload.php');
  ```

### Manual
  - Se você não quer usar o Composer
  - Faça o download de uma das últimas versões, usando o endereço abaixo

  [`github.com/nfe/client-php/releases`](https://github.com/nfe/client-php/releases)

  - Depois de baixar, inclua a biblioteca em seu arquivo PHP

  ```php
  require_once("caminho-para/client-php/lib/init.php");
  ```

## Dependencias

  Esta biblioteca requer as seguintes extensões para funcionamento correto:

  - [`curl`](https://secure.php.net/manual/en/book.curl.php)
  - [`json`](https://secure.php.net/manual/en/book.json.php)

  Se você usa o Composer, essas dependencias são gerenciadas automaticamente. Caso teha feito a instalação manual, você precisa ter certeza que estas extensões estão instaladas e disponíveis.

## Exemplos de Uso

### Criar empresa
```php
NFe_io::setApiKey("c73d49f9649046eeba36dcf69f6334fd"); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$companyCreated = NFe_Company::create(
  array(
    'federalTaxNumber' => 87502637000164, // Use esse gerador para testar: http://www.geradordecnpj.org/
    'name'             => 'BANCO DO BRASIL SA',
    'tradeName'        => 'BANCO DO BRASIL',
    'email'            => 'nfe@mailinator.com', // Para visualizar os e-mails https://www.mailinator.com/inbox2.jsp?public_to=nfe
     // Endereço da empresa
    'address'          => array(
      // Código do pais com três letras
      'country'               => 'BRA',
      // CEP do endereço (opcional para tomadores no exterior)
      'postalCode'            => '70073901',
      // Logradouro
      'street'                => 'Outros Quadra 1 Bloco G Lote 32',
      // Número (opcional)
      'number'                => 'S/N',
      // Complemento (opcional)
      'additionalInformation' => 'QUADRA 01 BLOCO G',
      // Bairro
      'district'              => 'Asa Sul',
      // Cidade é opcional para tomadores no exterior
      'city' => array(
          // Código do IBGE para a Cidade
          'code' => '5300108',
          // Nome da Cidade
          'name' => 'Brasilia'
      ),
      // Sigla do estado (opcional para tomadores no exterior)
      'state' => 'DF'
    )
  )
);

echo($companyCreated->id);
```

### Emitir nota fiscal
```php
NFe_io::setApiKey('c73d49f9649046eeba36dcf69f6334fd'); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$invoiceCreated = NFe_ServiceInvoice::create(
  // ID da empresa, você deve copiar exatamente como está no painel
  '64555e0ee340420fdc94ad09',
  // Dados da nota fiscal de serviço
  array(
    // Código do serviço de acordo com o a cidade
    'cityServiceCode' => '2690',
    // Descrição dos serviços prestados
    'description'     => 'TESTE EMISSAO',
    // Valor total do serviços
    'servicesAmount'  => 0.01,
    // Dados do Tomador dos Serviços
    'borrower' => array(
      // CNPJ ou CPF (opcional para tomadores no exterior)
      'federalTaxNumber' => 191,
      // Nome da pessoa física ou Razão Social da Empresa
      'name'             => 'BANCO DO BRASIL SA',
      // Email para onde deverá ser enviado a nota fiscal
      'email'            => 'nfe@mailinator.com', // Para visualizar os e-mails https://www.mailinator.com/
      // Endereço do tomador
      'address'          => array(
        // Código do pais com três letras
        'country'               => 'BRA',
        // CEP do endereço (opcional para tomadores no exterior)
        'postalCode'            => '70073901',
        // Logradouro
        'street'                => 'Outros Quadra 1 Bloco G Lote 32',
        // Número (opcional)
        'number'                => 'S/N',
        // Complemento (opcional)
        'additionalInformation' => 'QUADRA 01 BLOCO G',
        // Bairro
        'district'              => 'Asa Sul',
        // Cidade é opcional para tomadores no exterior
        'city' => array(
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

echo($invoiceCreated->id);
```

### Cancelar nota fiscal
```php
NFe_io::setApiKey("c73d49f9649046eeba36dcf69f6334fd"); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$invoice = NFe_ServiceInvoice::fetch(
  '64555e0ee340420fdc94ad09', // ID da empresa, você deve copiar exatamente como está no painel
  'wPi7i954QAcr6kmy17BtEKtN'  // ID da nota fiscal
);

if ( $invoice->status == 'Issued' ) {
  $invoice->cancel();
}
```

### Download do PDF da nota fiscal
```php
NFe_io::setApiKey('c73d49f9649046eeba36dcf69f6334fd'); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$url = NFe_ServiceInvoice::pdf(
  '64555e0ee340420fdc94ad09', // ID da empresa, você deve copiar exatamente como está no painel
  'wPi7i954QAcr6kmy17BtEKtN'  // ID da nota fiscal
);

file_put_contents( './invoice_file.pdf', file_get_contents($url) );
```

## Documentação

Acesse [https://api.nfe.io](https://api.nfe.io) para mais referências da API.

## Testes

Instale as dependências. O client-php do NFe utiliza [SimpleTest](http://simpletest.org/).
``` bash
composer update --dev
```

Execute a comitiva de testes:
``` bash
php ./test/NFe.php
```

## Autor

Originalmente criado pela equipe da [NFe.io](https://github.com/orgs/nfe/people)
