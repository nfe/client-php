# NFe.io para PHP

## Requisitos

* PHP 5.3+

## Instalação

Faça o download da biblioteca:

~~~
git clone https://github.com/nfe/client-php
~~~

Inclua a biblioteca em seu arquivo PHP:

~~~
require_once(".../nfe-php/lib/Nfe.php");
~~~

### Usando Composer

~~~
$ composer require nfe/nfe
~~~

O autoload do composer irá cuidar do resto.

## Exemplo de Uso

### Criar empresa
~~~
Nfe::setApiKey("c73d49f9649046eeba36dcf69f6334fd"); // Ache sua chave API no Painel

Nfe_Company::create(
  Array(
    "federalTaxNumber" => 191,
    "name" => "BANCO DO BRASIL SA",
    "tradeName" => "BANCO DO BRASIL",
    "email" => "exemplo@bb.com.br"
  )
);
~~~

## Documentação

Acesse [api.nfe.io/swagger](http://api.nfe.io/swagger) para referência

## Testes

Instale as dependências. NFe-PHP utiliza SimpleTest.

~~~
composer update --dev
~~~

Execute a comitiva de testes:
~~~
php ./test/Nfe.php
~~~
