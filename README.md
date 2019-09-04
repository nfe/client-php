# Cliente PHP para emissão de notas fiscais - NFe.io

## Onde acessar a documentação da API?

> Acesse a [nossa documentação](https://nfe.io/doc/rest-api/nfe-v1) para mais detalhes e referências.

## Como realizar a instalação do pacote?

  Você pode instalar via [Composer](http://getcomposer.org/), executando o comando a seguir:

  ```bash
  composer require nfe/nfe
  ```

  Para usar a biblioteca, use o [Composer autoload](https://getcomposer.org/doc/00-intro.md#autoloading):

  ```php
  require_once('vendor/autoload.php');
  ```
> **Observação**: A versão do PHP deverá ser 5.4 ou superior.

## Dependencias

  Esta biblioteca requer as seguintes extensões para funcionamento correto:

  **-** [`curl`](https://secure.php.net/manual/en/book.curl.php)

  **-** [`json`](https://secure.php.net/manual/en/book.json.php)

  Se você usa o Composer, essas dependencias são gerenciadas automaticamente. Caso tenha feito a instalação manual, você precisa ter certeza que estas extensões estão instaladas e disponíveis.

 > Se você não quiser utilizar o Composer, você pode fazer o download de uma das últimas versões, utilizando o endereço
[https://github.com/nfe/client-php/releases](https://github.com/nfe/client-php/releases)

## Exemplos de uso

  Depois de baixar o pacote, inclua a biblioteca em seu arquivo PHP, utilizando o código abaixo:

  ```php
  require_once("caminho-para/client-php/lib/init.php");
  ```
  > **Observação**: Caso você utilizar mais de um arquivo .php para fazer a integração, o código acima deverá ser replicado nos outros arquivos.

### Como emitir uma Nota Fiscal de Serviço?
Abaixo, temos um código-exemplo para realizar uma Emissão de Nota Fiscal de Serviço:

```php
require_once("caminho-para/client-php/lib/init.php");

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

### Como cancelar uma nota?
Abaixo, temos um código-exemplo para efetuar o cancelamento de uma nota: 

```php
require_once("caminho-para/client-php/lib/init.php");

NFe_io::setApiKey("c73d49f9649046eeba36dcf69f6334fd"); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$invoice = NFe_ServiceInvoice::fetch(
  '64555e0ee340420fdc94ad09', // ID da empresa, você deve copiar exatamente como está no painel
  'wPi7i954QAcr6kmy17BtEKtN'  // ID da nota fiscal
);

if ( $invoice->status == 'Issued' ) {
  $invoice->cancel();
}
```
### Como criar uma empresa para realizar a emissão de notas fiscais?
Abaixo, temos um código-exemplo de criação de uma empresa, para realizar a emissão de nota fiscal:

```php
require_once("caminho-para/client-php/lib/init.php");

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

### Como efetuar o download de uma nota em PDF?
Abaixo, temos um código exemplo para baixar uma nota em PDF:

```php
require_once("caminho-para/client-php/lib/init.php");

NFe_io::setApiKey('c73d49f9649046eeba36dcf69f6334fd'); // Ache sua chave API no painel (https://app.nfe.io/account/apikeys)

$url = NFe_ServiceInvoice::pdf(
  '64555e0ee340420fdc94ad09', // ID da empresa, você deve copiar exatamente como está no painel
  'wPi7i954QAcr6kmy17BtEKtN'  // ID da nota fiscal
);

file_put_contents( './invoice_file.pdf', file_get_contents($url) );
```

### Como validar o Webhook?

PHP > 5.3
```
define('WEBHOOK_SECRET_KEY', 'COLOQUE SUA CHAVE DEFINIDA NO SITE AQUI');
function verify_webhook($data, $hmac_header)
{
    $calculated_hmac = base64_encode(hash_hmac('sha1', $data, WEBHOOK_SECRET_KEY,true));
    return ($hmac_header == $calculated_hmac);
}

$hmac_header = str_replace("sha1=", '', $_SERVER['HTTP_X_NFEIO_SIGNATURE']);
$data = file_get_contents('php://input');
$verified = verify_webhook($data, $hmac_header);
if(!$verified) {
	//Código para requisição que não foi validada
} else {
	//Código para requisição validada
}
```
## Testes

Instale as dependências necessárias para executar os testes. O client-php do NFe utiliza [SimpleTest](http://simpletest.org/).
``` bash
composer update --dev
```

Execute a comitiva de testes:
``` bash
php ./test/NFe.php
```
