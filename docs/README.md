---
title: Biblioteca NFE.io em PHP para Emissão de Notas Fiscais (NFS-e, NF-e, NFC-e, CT-e)
description: SDK PHP oficial da NFE.io — PHP 8.2+, zero dependências de runtime, cliente único com recursos tipados e retorno discriminado por union types nativos.
sidebar_label: Biblioteca PHP
slug: /desenvolvedores/bibliotecas/php
provider: NFE.io
badge: SDK
layout_type: IntegrationLayout
heroImage: /docs/img/bibliotecas/php.svg
ctaLabel: GitHub NFE.io PHP
ctaUrl: https://github.com/nfe/client-php
---

# Biblioteca PHP NFE.io

SDK oficial da [NFE.io](https://nfe.io) para PHP: emissão e gestão de documentos
fiscais eletrônicos brasileiros (NFS-e, NF-e, NFC-e, CT-e) com PHP moderno e
**zero dependências de runtime**.

- **Cliente único** `Nfe\Client` com acessores `camelCase` por recurso, em
  propriedades `public readonly`.
- **PHP 8.2+ estrito** — `readonly`, enums, argumentos nomeados e **union types
  nativos** para o retorno discriminado da emissão assíncrona.
- **Paridade 1:1** com o [SDK Node.js](https://github.com/nfe/client-nodejs) —
  mesmos recursos, mesmos hosts, mesmos payloads.

## Requisitos

- PHP **8.2**, **8.3** ou **8.4**.
- Extensões: `ext-curl`, `ext-json`, `ext-mbstring`.
- **Zero dependências de runtime** — cURL nativo; PSR-3 (log) e PSR-18
  (transporte HTTP) são opcionais.

## Instalação

```sh
composer require "nfe/nfe:^3.0"
```

## Primeiros passos

```php
use Nfe\Client;

$nfe = new Client(apiKey: $_ENV['NFE_API_KEY']);

$result = $nfe->serviceInvoices->create('55df4dc6b6cd9007e4f13ee8', [
    'cityServiceCode' => '2690',
    'description'     => 'Manutenção e suporte técnico',
    'servicesAmount'  => 100.0,
    'borrower'        => [
        'federalTaxNumber' => 191,
        'name'             => 'Banco do Brasil SA',
    ],
]);
```

O fluxo completo (retorno discriminado + polling) está em
[Primeiros passos](./getting-started.md).

## Guias

| Guia | Conteúdo |
|---|---|
| [Primeiros passos](./getting-started.md) | Instalação, cliente, primeira emissão e polling. |
| [Configuração](./configuration.md) | Argumentos do cliente, `Nfe\Config`, modelo de duas chaves, retry e transportes. |
| [Emissão assíncrona e polling](./async-and-polling.md) | Contrato 202 `Pending`/`Issued` e o laço de polling com `FlowStatus`. |
| [Tratamento de erros](./errors.md) | Hierarquia `Nfe\Exception\*` e padrões de `catch`. |
| [Webhooks](./webhooks.md) | Verificação HMAC-SHA1 com `Nfe\Webhook` e idempotência/replay. |
| [Paginação](./pagination.md) | Estilos page e cursor; `ListResponse` e `ListPage`. |
| [Downloads](./downloads.md) | PDF/XML como bytes em `string`. |
| [Roteamento multi-host](./multi-host-routing.md) | Os hosts por família de recurso. |
| [Cookbook por recurso](./recursos/) | Um exemplo por recurso (os 17 recursos do cliente). |

## Migração

Vindo da série `2.x` (`NFe_io`, prefixo `NFe_`)? Veja o
[guia de migração](https://github.com/nfe/client-php/blob/master/MIGRATION.md) —
a `v3.0` é uma reescrita completa, sem camada de compatibilidade. As linhas v2 e
v3 compartilham o mesmo pacote Composer (`nfe/nfe`); o Composer resolve cada
constraint (`^2.0` / `^3.0`) para a major correta automaticamente.
