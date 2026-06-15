# SDK PHP da NFE.io

[![Licença](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Versão PHP](https://img.shields.io/badge/php-%5E8.2-787CB5)](composer.json)
[![Status](https://img.shields.io/badge/v3-em--desenvolvimento-orange)](https://github.com/nfe/client-php/tree/v3)

SDK PHP oficial da API [NFE.io](https://nfe.io). PHP 8.2+ moderno, zero dependências em runtime, projetado em paridade com o [SDK Node.js](https://github.com/nfe/client-nodejs).

> **Você está lendo a branch v3.** A v3 é uma reescrita completa e está em desenvolvimento. Para a versão estável v2, veja a [branch `master`](https://github.com/nfe/client-php/tree/master) e o pacote `nfe/nfe` no Packagist. A v2 está congelada e não receberá novas atualizações.

## Status

| Branch | Pacote | Situação |
|---|---|---|
| `v3` | `nfe/client-php` | 🚧 **Em desenvolvimento** — ainda não lançado |
| `master` | `nfe/nfe` | ❄️ Congelado (v2.5, sem novas atualizações) |

## Requisitos

- PHP 8.2, 8.3 ou 8.4
- Extensões: `ext-curl`, `ext-json`, `ext-mbstring`

## Instalação (quando a v3 for lançada)

```bash
composer require nfe/client-php
```

## Início rápido (API alvo da v3)

```php
use Nfe\Client;
use Nfe\Environment;

$nfe = new Client(
    apiKey: $_ENV['NFE_API_KEY'],
    environment: Environment::Production,
);

// Emite uma nota de serviço (NFS-e)
$result = $nfe->serviceInvoices->create($companyId, [
    'borrower' => [
        'federalTaxNumber' => 12345678901234,
        'name'             => 'Cliente exemplo',
        'email'            => 'cliente@example.com',
    ],
    'cityServiceCode'    => '01234',
    'federalServiceCode' => '01.02',
    'description'        => 'Serviço prestado',
    'servicesAmount'     => 1000.00,
]);

if ($result instanceof Nfe\Response\Pending) {
    // 202 — nota está sendo processada de forma assíncrona
    echo "Pendente — invoiceId: {$result->invoiceId()}\n";
} else {
    // 201 — nota emitida imediatamente
    echo "Emitida: {$result->resource()->id}\n";
}
```

## Duas chaves de API (emissão vs. serviços de dados)

A plataforma NFE.io separa o faturamento entre a API principal (emissão,
empresas, webhooks — cobrada por documento) e a API de serviços de dados
(consultas de CEP/CNPJ/CPF, query de NF-e/NFC-e — cobrada por consulta,
tipicamente um plano separado). Alguns integradores possuem uma única chave
com ambos os planos; outros possuem duas chaves distintas.

O SDK aceita as duas. Passe `dataApiKey` quando você tiver uma chave
dedicada para serviços de dados; o SDK roteia `addresses`,
`legalEntityLookup`, `naturalPersonLookup`, `productInvoiceQuery` e
`consumerInvoiceQuery` para ela. Quando `dataApiKey` é omitido, essas
chamadas caem por padrão na `apiKey` — mesma cadeia do `resolveDataApiKey()`
do SDK Node.

```php
$nfe = new Client(
    apiKey:     $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
);

// Roteado via apiKey (API principal)
$nfe->serviceInvoices->retrieve($companyId, $invoiceId);

// Roteado via dataApiKey quando definida; senão, via apiKey
$nfe->addresses->lookupByPostalCode('01310-100');
$nfe->legalEntityLookup->getBasicInfo('12.345.678/0001-90');
```

> Se você ver `Nfe\Exception\AuthorizationException` (HTTP 403) em chamadas
> de consulta, a causa mais provável é que a chave em uso não possui o plano
> de serviços de dados. Provisione uma `dataApiKey` com o plano de dados e o
> SDK fará o roteamento automaticamente.

## Recursos (paridade com o SDK Node.js)

| Propriedade | Família de endpoints |
|---|---|
| `$nfe->serviceInvoices` | NFS-e (nota de serviço) |
| `$nfe->productInvoices` | NF-e (nota de produto) |
| `$nfe->consumerInvoices` | NFC-e (nota ao consumidor) — emissão + consulta |
| `$nfe->transportationInvoices` | CT-e |
| `$nfe->inboundProductInvoices` | NF-e de entrada |
| `$nfe->productInvoiceQuery` | Consulta de NF-e |
| `$nfe->consumerInvoiceQuery` | Consulta de NFC-e |
| `$nfe->companies` | Gestão de empresas |
| `$nfe->legalPeople` | Pessoa jurídica (PJ) |
| `$nfe->naturalPeople` | Pessoa física (PF) |
| `$nfe->webhooks` | Configuração de webhooks |
| `$nfe->addresses` | Consulta de CEP |
| `$nfe->legalEntityLookup` | Consulta de CNPJ |
| `$nfe->naturalPersonLookup` | Consulta de CPF |
| `$nfe->taxCalculation` | Cálculo de impostos |
| `$nfe->taxCodes` | Códigos fiscais (NBS / CNAE) |
| `$nfe->stateTaxes` | Inscrição estadual |

## Verificação de assinatura de webhook

O SDK fornece um helper estático alinhado ao esquema canônico usado pela NFE.io (HMAC-SHA1 sobre `X-Hub-Signature`):

```php
use Nfe\Webhook;
use Nfe\Exception\SignatureVerificationException;

try {
    $event = Webhook::constructEvent(
        payload:   file_get_contents('php://input'),
        sigHeader: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
        secret:    $_ENV['NFE_WEBHOOK_SECRET'],
    );
    // $event é um WebhookEvent tipado
} catch (SignatureVerificationException $e) {
    http_response_code(403);
    exit;
}
```

## Polling (manual na v3.0)

Para emissão assíncrona de notas (HTTP 202), a v3.0 retorna uma resposta discriminada `Pending | Issued`. Um helper `pollUntilComplete()` chegará em uma release 3.x posterior; até lá, faça o loop manualmente em um worker/CLI:

```php
use Nfe\Util\FlowStatus;

$result = $nfe->serviceInvoices->create($companyId, $data);

if ($result instanceof Nfe\Response\Pending) {
    $invoiceId = $result->invoiceId();
    do {
        sleep(2);
        $invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
    } while (!FlowStatus::isTerminal($invoice->flowStatus));
}
```

`FlowStatus::TERMINAL` lista os quatro estados terminais (`Issued`, `IssueFailed`, `Cancelled`, `CancelFailed`). Espelha `TERMINAL_FLOW_STATES` do SDK Node.

## Tratamento de erros

Toda resposta não-2xx é mapeada para uma exceção tipada que estende `Nfe\Exception\ApiErrorException`. Capture a classe base para um handler genérico, ou a subclasse para uma recuperação direcionada:

| HTTP | Exceção | Causa típica |
|---|---|---|
| 400 | `InvalidRequestException` | Payload mal formado, falha de validação |
| 401 | `AuthenticationException` | Chave de API ausente ou inválida |
| 403 | `AuthorizationException` | Chave válida, mas o plano/escopo recusa a ação (ex.: requer chave de serviços de dados) |
| 404 | `NotFoundException` | Recurso não existe |
| 429 | `RateLimitException` | Throttling — consulte `Retry-After` |
| 5xx | `ServerException` | Falha na infraestrutura upstream / NFE.io |
| — | `ApiConnectionException` | Falha de rede, DNS, TLS, timeout |
| — | `SignatureVerificationException` | Assinatura do payload do webhook não confere |

```php
use Nfe\Exception\ApiErrorException;
use Nfe\Exception\AuthorizationException;
use Nfe\Exception\RateLimitException;

try {
    $nfe->addresses->lookupByPostalCode($cep);
} catch (AuthorizationException $e) {
    // 403 — chave provavelmente sem o plano de serviços de dados
    error_log("Consulta negada: {$e->getMessage()}");
} catch (RateLimitException $e) {
    // Consulte $e->responseHeaders['retry-after']
    throw $e;
} catch (ApiErrorException $e) {
    // Qualquer outra resposta não-2xx
    error_log("Erro na API {$e->statusCode}: {$e->getMessage()}");
}
```

Cada exceção expõe `$statusCode`, `$responseBody`, `$responseHeaders` e `$errorCode` para diagnóstico.

## Migrando da v2

Veja [MIGRATION.md](MIGRATION.md) para o mapeamento completo v2 → v3. Não há retrocompatibilidade — a v3 é uma reescrita limpa.

## Contribuindo

Veja [CONTRIBUTING.md](CONTRIBUTING.md).

## Licença

MIT — veja [LICENSE](LICENSE).
