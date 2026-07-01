# SDK PHP da NFE.io

[![Licença](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Versão PHP](https://img.shields.io/badge/php-%5E8.2-787CB5)](composer.json)
[![Status](https://img.shields.io/badge/v3-em--desenvolvimento-orange)](https://github.com/nfe/client-php/tree/v3)

SDK PHP oficial da API [NFE.io](https://nfe.io). PHP 8.2+ moderno, zero dependências em runtime, projetado em paridade com o [SDK Node.js](https://github.com/nfe/client-nodejs).

> **Você está lendo a branch v3.** A v3 é uma reescrita completa e está em RC. Para a versão estável v2, veja a [branch `master`](https://github.com/nfe/client-php/tree/master). A v2 está congelada e não receberá novas atualizações.

## Status

| Branch | Versões no Packagist (`nfe/nfe`) | Situação |
|---|---|---|
| `v3` | `^3.0` (atualmente `v3.0.0-rc.x`) | 🚧 **Em RC** — feedback bem-vindo |
| `master` | `^2.0` (congelado em `2.5`) | ❄️ Congelado, sem novas atualizações |

A v2 e a v3 compartilham o mesmo slug Packagist (`nfe/nfe`). Composer resolve cada constraint para a major correta automaticamente.

## Requisitos

- PHP 8.2, 8.3 ou 8.4
- Extensões: `ext-curl`, `ext-json`, `ext-mbstring`

## Instalação

```bash
# v3 RC (atual)
composer require "nfe/nfe:^3.0.0-rc" --stability=RC

# v3 estável (após GA)
composer require nfe/nfe:^3.0

# v2 legada (congelada)
composer require nfe/nfe:^2.0
```

### Skill para agentes de IA

Além do pacote de código, este repositório publica uma **skill de agente** (`nfeio-php-sdk`)
que ensina assistentes de IA (Claude Code, Cursor, Copilot, etc.) a usar o SDK corretamente.
São **dois canais distintos**:

| Canal | Comando | O quê |
|---|---|---|
| Código (Composer / Packagist) | `composer require nfe/nfe` | O SDK PHP |
| Skill de agente ([skills.sh](https://www.skills.sh/)) | `npx skills add https://github.com/nfe/client-php --skill nfeio-php-sdk` | O guia de uso para agentes |

O atalho `npx skills add nfe/client-php` também funciona. A skill é lida da árvore do
GitHub (slug `nfe/client-php`); ela **não** é baixada pelo `composer require` (fica fora
do dist via `.gitattributes` `export-ignore`).

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

## Exemplos por recurso

Os blocos abaixo cobrem os usos mais comuns de cada família. Os tipos de retorno declarados em cada método são a fonte da verdade — IDEs com PHP 8.2+ resolvem tudo via type hints.

<details>
<summary><strong>Notas de Serviço (NFS-e) — <code>$nfe->serviceInvoices</code></strong></summary>

```php
// Emitir nota (assíncrono — retorna Pending|Issued)
$result = $nfe->serviceInvoices->create($companyId, [
    'borrower' => [
        'federalTaxNumber' => 12345678901,
        'name'             => 'João da Silva',
        'email'            => 'joao@example.com',
    ],
    'cityServiceCode' => '10677',
    'description'     => 'Consultoria',
    'servicesAmount'  => 1500.00,
]);

// Listar com filtros e paginação (pageIndex é 1-based)
$lista = $nfe->serviceInvoices->list($companyId, [
    'pageCount' => 50,
    'pageIndex' => 1,
    'issuedBegin' => '2026-01-01',
    'issuedEnd'   => '2026-01-31',
]);
foreach ($lista->data as $nota) {
    echo "{$nota->id} → {$nota->flowStatus}\n";
}

// Consultar, status, cancelar, reenviar por email
$nota   = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
$status = $nfe->serviceInvoices->getStatus($companyId, $invoiceId);
$nfe->serviceInvoices->cancel($companyId, $invoiceId);
$nfe->serviceInvoices->sendEmail($companyId, $invoiceId); // envia para o email do tomador

// Baixar PDF e XML (retornam bytes brutos)
file_put_contents('nota.pdf', $nfe->serviceInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('nota.xml', $nfe->serviceInvoices->downloadXml($companyId, $invoiceId));
```

</details>

<details>
<summary><strong>Notas de Produto (NF-e) — <code>$nfe->productInvoices</code></strong></summary>

Ciclo completo: emissão, listagem, consulta, cancelamento, carta de correção (CC-e) e inutilização de faixa.

```php
// Emitir NF-e (assíncrono)
$result = $nfe->productInvoices->create($companyId, [
    'operationNature' => 'Venda de mercadoria',
    'operationType'   => 'Outgoing',
    'buyer' => ['name' => 'Empresa LTDA', 'federalTaxNumber' => 12345678000190],
    'items' => [[
        'code' => 'PROD-001',
        'description' => 'Produto X',
        'quantity' => 1,
        'unitAmount' => 100.00,
    ]],
]);

// Listar (environment obrigatório: Production | Development)
$lista = $nfe->productInvoices->list($companyId, [
    'environment' => 'Production',
    'pageCount'   => 10,
]);

// Carta de correção (CC-e) — razão entre 15 e 1000 caracteres
$nfe->productInvoices->sendCorrectionLetter(
    $companyId,
    $invoiceId,
    'Correcao do endereco do destinatario conforme novo cadastro',
);

// Inutilizar faixa de numeração
$nfe->productInvoices->disableRange($companyId, [
    'environment' => 'Production',
    'serie'       => 1,
    'state'       => 'SP',
    'beginNumber' => 100,
    'lastNumber'  => 110,
]);

// Downloads
$pdf = $nfe->productInvoices->downloadPdf($companyId, $invoiceId);
$xml = $nfe->productInvoices->downloadXml($companyId, $invoiceId);
$ccePdf = $nfe->productInvoices->downloadCorrectionLetterPdf($companyId, $invoiceId);
```

> Emissão, cancelamento, CC-e e inutilização são assíncronos (HTTP 202/204). A conclusão chega via webhook.

</details>

<details>
<summary><strong>Notas ao Consumidor (NFC-e) — <code>$nfe->consumerInvoices</code></strong></summary>

NFC-e segue o mesmo padrão de NF-e (emissão assíncrona, downloads, cancelamento, inutilização).

```php
$result = $nfe->consumerInvoices->create($companyId, $payload);
$lista  = $nfe->consumerInvoices->list($companyId, ['environment' => 'Production']);
$nota   = $nfe->consumerInvoices->retrieve($companyId, $invoiceId);
$nfe->consumerInvoices->cancel($companyId, $invoiceId);

// Downloads (retornam bytes)
file_put_contents('nfce.pdf', $nfe->consumerInvoices->downloadPdf($companyId, $invoiceId));
file_put_contents('nfce.xml', $nfe->consumerInvoices->downloadXml($companyId, $invoiceId));

// Inutilizar faixa
$nfe->consumerInvoices->disableRange($companyId, [
    'environment' => 'Production',
    'serie'       => 1,
    'state'       => 'SP',
    'beginNumber' => 1000,
    'lastNumber'  => 1010,
]);
```

</details>

<details>
<summary><strong>CT-e — <code>$nfe->transportationInvoices</code></strong></summary>

Consulta de CT-e via Distribuição DFe. Requer certificado A1 válido na empresa.

```php
// Ativar busca automática
$settings = $nfe->transportationInvoices->enable($companyId, [
    'startFromNsu' => 12345, // opcional
]);

// Verificar configuração atual / desativar
$config = $nfe->transportationInvoices->getSettings($companyId);
$nfe->transportationInvoices->disable($companyId);

// Consultar CT-e por chave (44 dígitos)
$accessKey = '35240112345678000190570010000001231234567890';
$cte = $nfe->transportationInvoices->retrieve($companyId, $accessKey);
echo "Remetente: {$cte->nameSender}, valor: {$cte->totalInvoiceAmount}";

// Baixar XML
file_put_contents('cte.xml',
    $nfe->transportationInvoices->downloadXml($companyId, $accessKey),
);

// Evento + XML do evento
$evento = $nfe->transportationInvoices->getEvent($companyId, $accessKey, $eventKey);
$eventoXml = $nfe->transportationInvoices->downloadEventXml($companyId, $accessKey, $eventKey);
```

</details>

<details>
<summary><strong>NF-e de Entrada (Distribuição) — <code>$nfe->inboundProductInvoices</code></strong></summary>

Recebe NF-e emitidas contra a empresa via Distribuição DFe.

```php
// Ativar busca automática
$nfe->inboundProductInvoices->enableAutoFetch($companyId, [
    'environmentSEFAZ' => 'Production',
    'webhookVersion'   => '2',
]);

// Consultar NF-e por chave (formato webhook v2 — recomendado)
$accessKey = '35240112345678000190550010000001231234567890';
$nota = $nfe->inboundProductInvoices->getProductInvoiceDetails($companyId, $accessKey);
echo "Emissor: {$nota['issuer']['name']}";

// Baixar XML, PDF (DANFE) e JSON
file_put_contents('nfe.xml', $nfe->inboundProductInvoices->getXml($companyId, $accessKey));
file_put_contents('nfe.pdf', $nfe->inboundProductInvoices->getPdf($companyId, $accessKey));
$json = $nfe->inboundProductInvoices->getJson($companyId, $accessKey);

// Manifestação (Ciência da Operação por padrão = 210210)
$nfe->inboundProductInvoices->manifest($companyId, $accessKey);
$nfe->inboundProductInvoices->manifest($companyId, $accessKey, 210220); // Confirmação da Operação

// Reprocessar um webhook entregue
$nfe->inboundProductInvoices->reprocessWebhook($companyId, $accessKey);
```

| Código | Evento de manifestação |
|---|---|
| `210210` | Ciência da Operação (padrão) |
| `210220` | Confirmação da Operação |
| `210240` | Operação não Realizada |

</details>

<details>
<summary><strong>Consulta de NF-e na SEFAZ — <code>$nfe->productInvoiceQuery</code></strong></summary>

Consulta NF-e diretamente na SEFAZ pela chave de acesso. Read-only, sem escopo de empresa. Usa `dataApiKey` quando configurada.

```php
$accessKey = '35240112345678000190550010000001231234567890';

$nota   = $nfe->productInvoiceQuery->retrieve($accessKey);
$pdf    = $nfe->productInvoiceQuery->downloadPdf($accessKey);
$xml    = $nfe->productInvoiceQuery->downloadXml($accessKey);
$eventos = $nfe->productInvoiceQuery->listEvents($accessKey);
```

</details>

<details>
<summary><strong>Consulta de CFe-SAT / NFC-e na SEFAZ — <code>$nfe->consumerInvoiceQuery</code></strong></summary>

```php
$accessKey = '35240112345678000190590000000012341234567890';

$cupom = $nfe->consumerInvoiceQuery->retrieve($accessKey);
file_put_contents('cfe.xml', $nfe->consumerInvoiceQuery->downloadXml($accessKey));
```

</details>

<details>
<summary><strong>Empresas — <code>$nfe->companies</code></strong></summary>

```php
// CRUD
$empresa = $nfe->companies->create([
    'federalTaxNumber' => 12345678000190,
    'name'             => 'Minha Empresa LTDA',
    'email'            => 'empresa@example.com',
    // ... endereço, regime tributário, etc.
]);

$lista     = $nfe->companies->list();
$todas     = $nfe->companies->listAll(); // sem paginação — itera tudo
$empresa   = $nfe->companies->retrieve($companyId);
$atualizada = $nfe->companies->update($companyId, ['email' => 'novo@example.com']);
$nfe->companies->remove($companyId);

// Buscas
$empresa = $nfe->companies->findByTaxNumber('12345678000190');
$matches = $nfe->companies->findByName('Minha Empresa');

// Certificado A1
$status = $nfe->companies->getCertificateStatus($companyId);
if ($status->hasCertificate && $status->isExpiringSoon) {
    echo "Expira em {$status->daysUntilExpiration} dia(s)\n";
}

// Painel de certificados (filtros prontos)
$comCert     = $nfe->companies->getCompaniesWithCertificates();
$expirando   = $nfe->companies->getCompaniesWithExpiringCertificates(thresholdDays: 30);
```

</details>

<details>
<summary><strong>Pessoas (PJ e PF) — <code>$nfe->legalPeople</code> / <code>$nfe->naturalPeople</code></strong></summary>

Ambos os recursos têm a mesma forma. Exemplo com PJ:

```php
$pj = $nfe->legalPeople->create($companyId, [
    'federalTaxNumber' => '12345678000190',
    'name'             => 'Cliente PJ',
    'email'            => 'pj@example.com',
]);

$lista     = $nfe->legalPeople->list($companyId);
$pj        = $nfe->legalPeople->retrieve($companyId, $legalPersonId);
$atualizada = $nfe->legalPeople->update($companyId, $legalPersonId, ['email' => 'novo@x.com']);
$nfe->legalPeople->delete($companyId, $legalPersonId);

// Buscar por documento
$pj = $nfe->legalPeople->findByTaxNumber($companyId, '12345678000190');

// Criação em lote
$nfe->legalPeople->createBatch($companyId, [
    ['federalTaxNumber' => '11111111000111', 'name' => 'PJ 1', 'email' => 'a@x.com'],
    ['federalTaxNumber' => '22222222000122', 'name' => 'PJ 2', 'email' => 'b@x.com'],
]);
```

Para pessoas físicas (`$nfe->naturalPeople`), troque `federalTaxNumber` (CPF de 11 dígitos) e use os mesmos métodos.

</details>

<details>
<summary><strong>Inscrições Estaduais — <code>$nfe->stateTaxes</code></strong></summary>

Necessário para emitir NF-e de produto.

```php
$lista = $nfe->stateTaxes->list($companyId);

$ie = $nfe->stateTaxes->create($companyId, [
    'taxNumber'       => '123456789',
    'serie'           => 1,
    'number'          => 1,
    'code'            => 'SP',
    'environmentType' => 'production',
    'type'            => 'nFe',
]);

$ie = $nfe->stateTaxes->retrieve($companyId, $stateTaxId);
$nfe->stateTaxes->update($companyId, $stateTaxId, ['serie' => 2]);
$nfe->stateTaxes->delete($companyId, $stateTaxId);
```

</details>

<details>
<summary><strong>Consulta de CEP — <code>$nfe->addresses</code></strong></summary>

```php
// Por CEP (8 dígitos, com ou sem hífen). O host address.api.nfe.io/v2
// suporta apenas consulta por CEP.
$resultado = $nfe->addresses->lookupByPostalCode('01310-100');
foreach ($resultado->addresses as $end) {
    echo "{$end['street']}, {$end['city']['name']}/{$end['state']}\n";
}
```

</details>

<details>
<summary><strong>Consulta de CNPJ — <code>$nfe->legalEntityLookup</code></strong></summary>

```php
// Dados cadastrais (Receita Federal)
$result = $nfe->legalEntityLookup->getBasicInfo('12.345.678/0001-90');
$pj = $result->legalEntity;
echo "Razão Social: {$pj['name']}, Status: {$pj['status']}";

// Com opções (atualizar endereço/código IBGE via Correios)
$result = $nfe->legalEntityLookup->getBasicInfo('12345678000190', [
    'updateAddress'  => false,
    'updateCityCode' => true,
]);

// IE por estado
$ieSP = $nfe->legalEntityLookup->getStateTaxInfo('SP', '12345678000190');

// Avaliar IE para emissão de nota
$avaliacao = $nfe->legalEntityLookup->getStateTaxForInvoice('MG', '12345678000190');

// Melhor IE sugerida
$sugestao = $nfe->legalEntityLookup->getSuggestedStateTaxForInvoice('SP', '12345678000190');
```

</details>

<details>
<summary><strong>Consulta de CPF — <code>$nfe->naturalPersonLookup</code></strong></summary>

```php
// CPF + data de nascimento (string YYYY-MM-DD ou DateTimeImmutable)
$result = $nfe->naturalPersonLookup->getStatus('123.456.789-01', '1990-01-15');
echo "Nome: {$result->name}, Situação: {$result->status}";

// DateTimeImmutable também funciona
$result = $nfe->naturalPersonLookup->getStatus(
    '12345678901',
    new DateTimeImmutable('1990-01-15'),
);
```

</details>

<details>
<summary><strong>Cálculo de Impostos — <code>$nfe->taxCalculation</code></strong></summary>

Engine de cálculo ICMS / ICMS-ST / PIS / COFINS / IPI / II.

```php
$resultado = $nfe->taxCalculation->calculate($tenantId, [
    'operationType' => 'Outgoing',
    'issuer'    => ['state' => 'SP', 'taxRegime' => 'RealProfit'],
    'recipient' => ['state' => 'RJ'],
    'items' => [[
        'id'            => 'item-1',
        'operationCode' => 121,
        'origin'        => 'National',
        'ncm'           => '61091000',
        'quantity'      => 10,
        'unitAmount'    => 100.00,
    ]],
]);

foreach ($resultado['items'] ?? [] as $item) {
    echo "Item {$item['id']}: CFOP={$item['cfop']}\n";
    echo "  ICMS: CST={$item['icms']['cst']}, valor={$item['icms']['vICMS']}\n";
}
```

</details>

<details>
<summary><strong>Códigos auxiliares — <code>$nfe->taxCodes</code></strong></summary>

```php
$operacoes        = $nfe->taxCodes->listOperationCodes(['pageIndex' => 1, 'pageCount' => 20]);
$finalidades      = $nfe->taxCodes->listAcquisitionPurposes();
$perfisEmissor    = $nfe->taxCodes->listIssuerTaxProfiles();
$perfisDestinatario = $nfe->taxCodes->listRecipientTaxProfiles();

foreach ($operacoes->items as $cod) {
    echo "{$cod['code']} — {$cod['description']}\n";
}
```

</details>

## Webhooks

### Configurar um webhook

```php
$webhook = $nfe->webhooks->create($companyId, [
    'url'    => 'https://meuapp.com.br/api/webhooks/nfe',
    'events' => ['invoice.issued', 'invoice.cancelled', 'invoice.error'],
    'active' => true,
]);

// Listar / atualizar / remover / testar
$lista = $nfe->webhooks->list($companyId);
$nfe->webhooks->update($companyId, $webhookId, ['events' => ['invoice.issued']]);
$nfe->webhooks->delete($companyId, $webhookId);
$nfe->webhooks->test($companyId, $webhookId);

// Listar eventos suportados pela API
$eventos = $nfe->webhooks->getAvailableEvents();
```

### Verificar assinatura no endpoint

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
    // $event é um WebhookEvent tipado: $event->type, $event->data, $event->id, $event->createdAt
} catch (SignatureVerificationException $e) {
    http_response_code(403);
    exit;
}

// Roteamento por tipo de evento
match ($event->type) {
    'invoice.issued'    => emitidaHandler($event->data),
    'invoice.cancelled' => canceladaHandler($event->data),
    'invoice.error'     => erroHandler($event->data),
    default             => null,
};

http_response_code(200);
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

## Opções de configuração avançadas

Quando você precisa de mais controle (timeout, retry, transporte customizado, logger), construa um `Config` explícito:

```php
use Nfe\Client;
use Nfe\Config;
use Nfe\Environment;
use Nfe\Http\RetryPolicy;
use Psr\Log\LoggerInterface;

$config = new Config(
    apiKey:     $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
    environment: Environment::Production,
    timeout: 60, // segundos
    retry: new RetryPolicy(
        maxRetries: 3,
        baseDelay: 1.0,  // segundos
        maxDelay: 30.0,  // segundos
        jitter: 0.3,     // ±30%
    ),
    logger: $myPsr3Logger,            // opcional, qualquer LoggerInterface
    transport: $myCustomTransport,    // opcional, ex.: adaptador PSR-18
    userAgentSuffix: 'MeuApp/1.2.3',  // sufixo identificador no User-Agent
);

$nfe = new Client(config: $config);
```

| Campo | Padrão | Descrição |
|---|---|---|
| `apiKey` | obrigatório | Chave principal (emissão, empresas, webhooks). |
| `dataApiKey` | `null` | Chave separada para serviços de dados. Quando `null`, faz fallback para `apiKey`. |
| `environment` | `Production` | `Production` ou `Sandbox`. |
| `timeout` | `60` | Timeout HTTP por requisição (segundos). |
| `retry` | `new RetryPolicy()` | Backoff exponencial com jitter simétrico. Use `RetryPolicy::none()` para desabilitar. |
| `transport` | `CurlTransport` | Implementação de `Nfe\Http\Transport` (ex.: adaptador PSR-18). |
| `logger` | `null` | Qualquer `Psr\Log\LoggerInterface`. PSR-3 **não** é dependência em runtime. |
| `userAgentSuffix` | `null` | Identificador do integrador (ex.: `WHMCS/8.10`). |

### Override por chamada

`RequestOptions` sobrescreve `apiKey`, `baseUrl` e `timeout` em uma chamada específica — útil em integrações multi-tenant:

```php
use Nfe\Http\RequestOptions;

$nota = $nfe->serviceInvoices->retrieve(
    $companyId,
    $invoiceId,
    options: new RequestOptions(apiKey: $chaveDoCliente, timeout: 120),
);
```

## Variáveis de ambiente

O SDK **não lê variáveis de ambiente automaticamente** — você passa as chaves no construtor. Convenção sugerida (compatível com o SDK Node):

| Variável | Uso |
|---|---|
| `NFE_API_KEY` | Chave principal. |
| `NFE_DATA_API_KEY` | Chave separada para CEP/CNPJ/CPF/NF-e query. |
| `NFE_WEBHOOK_SECRET` | Segredo HMAC do webhook. |

```php
$nfe = new Client(
    apiKey:     getenv('NFE_API_KEY') ?: throw new RuntimeException('NFE_API_KEY ausente'),
    dataApiKey: getenv('NFE_DATA_API_KEY') ?: null,
);
```

## Migrando da v2

Veja [MIGRATION.md](MIGRATION.md) para o mapeamento completo v2 → v3. Não há retrocompatibilidade — a v3 é uma reescrita limpa.

## Contribuindo

Veja [CONTRIBUTING.md](CONTRIBUTING.md).

## Licença

MIT — veja [LICENSE](LICENSE).
