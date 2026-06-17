# Migrando da v2 para a v3

> A v3 é uma reescrita completa. Não há camada de compatibilidade, alias ou
> caminho de upgrade direto. Este documento mapeia os padrões mais comuns da
> v2 para seus equivalentes na v3.

## Nome do pacote Composer

| v2 | v3 |
|---|---|
| `composer require nfe/nfe` | `composer require nfe/client-php` |

`nfe/nfe` e `nfe/client-php` são pacotes Packagist distintos e podem coexistir temporariamente no mesmo projeto durante a migração. Quando seu código estiver inteiramente na v3, remova o `nfe/nfe`.

## Versão do PHP

| v2 | v3 |
|---|---|
| PHP 5.4+ | PHP 8.2, 8.3 ou 8.4 |

## Namespace e nomes de classe

A v2 usava o prefixo achatado `NFe_` sem namespaces PHP. A v3 usa o namespace `Nfe\` com autoload PSR-4.

| v2 | v3 |
|---|---|
| `NFe_io` | `Nfe\Client` (instância, não estático) |
| `NFe_Company` | `Nfe\Client::companies` (recurso) |
| `NFe_ServiceInvoice` | `Nfe\Client::serviceInvoices` (recurso) |
| `NFe_LegalPerson` | `Nfe\Client::legalPeople` (recurso) |
| `NFe_NaturalPerson` | `Nfe\Client::naturalPeople` (recurso) |
| `NFe_Webhook` | `Nfe\Client::webhooks` (recurso); veja também o helper `Nfe\Webhook` |
| `NFe_APIRequest` | `Nfe\Http\CurlTransport` (interno) |

A v3 traz 17 recursos no total (a v2 não tinha equivalentes para a maioria). Veja a tabela completa no [README](README.md#recursos-paridade-com-o-sdk-nodejs).

## Configuração

```php
// v2
NFe_io::setApiKey('your-key');
NFe_io::setBaseURI('https://api.nfe.io/v1');

// v3
$nfe = new Nfe\Client(
    apiKey: 'your-key',
    environment: Nfe\Environment::Production,
);
```

A base URL não é mais configurada globalmente; a v3 roteia automaticamente por recurso para o host correto da NFE.io (api.nfe.io, api.nfse.io, address.api.nfe.io/v2, legalentity.api.nfe.io, naturalperson.api.nfe.io, nfe.api.nfe.io).

### Duas chaves de API (novidade da v3)

A NFE.io separa o faturamento entre a API principal (emissão, empresas, webhooks) e a API de serviços de dados (consultas de CEP/CNPJ/CPF, query de NF-e/NFC-e). Se você possui duas chaves distintas, passe ambas:

```php
$nfe = new Nfe\Client(
    apiKey:     $_ENV['NFE_API_KEY'],
    dataApiKey: $_ENV['NFE_DATA_API_KEY'] ?? null,
);
```

Quando `dataApiKey` é omitido, as consultas de dados fazem fallback para `apiKey`. Veja a seção [Duas chaves de API](README.md#duas-chaves-de-api-emiss%C3%A3o-vs-servi%C3%A7os-de-dados) no README.

## Chamadas estáticas viram chamadas de instância

```php
// v2 (estático / estilo active-record)
$company = NFe_Company::create(['name' => '...']);
$invoice = NFe_ServiceInvoice::create([...]);

// v3 (instância / estilo Stripe)
$company = $nfe->companies->create(['name' => '...']);
$invoice = $nfe->serviceInvoices->create($companyId, [...]);
```

## Tratamento de erros

A v2 retornava objetos com um campo `errors` e usava uma `NFeException` genérica. A v3 lança exceções tipadas que você pode capturar individualmente.

```php
// v2
$response = NFe_ServiceInvoice::create($attrs);
if (isset($response->errors)) { /* trata */ }

// v3
try {
    $invoice = $nfe->serviceInvoices->create($companyId, $data);
} catch (Nfe\Exception\AuthenticationException $e) {
    // 401 — chave ausente/inválida
} catch (Nfe\Exception\AuthorizationException $e) {
    // 403 — chave válida, mas plano/escopo recusa (ex.: precisa de dataApiKey)
} catch (Nfe\Exception\InvalidRequestException $e) {
    // 400 — $e->responseBody, $e->errorCode
} catch (Nfe\Exception\RateLimitException $e) {
    // 429 — o SDK já fez retry com backoff; surfaça para quem chamou
} catch (Nfe\Exception\ApiErrorException $e) {
    // qualquer outra resposta não-2xx da API
}
```

A tabela completa da hierarquia de exceções está em [Tratamento de erros](README.md#tratamento-de-erros) no README.

## Respostas assíncronas (HTTP 202)

A v2 não tinha um formato tipado para o padrão 202 + `Location` que a NFE.io usa em emissão assíncrona. A v3 retorna uma união discriminada:

```php
$result = $nfe->serviceInvoices->create($companyId, $data);

if ($result instanceof Nfe\Response\Pending) {
    // A API aceitou o pedido (HTTP 202) e está processando.
    $invoiceId = $result->invoiceId();
    $location  = $result->location();
} else {
    // HTTP 201 — nota emitida imediatamente.
    $invoice = $result->resource(); // DTO tipado (ServiceInvoice)
    echo $invoice->id;
}
```

> ⚠️ `invoiceId()` e `location()` são **métodos** no contrato `Pending`, não propriedades. O DTO emitido vem via `resource()`.

Um helper `pollUntilComplete()` **não** está incluído na v3.0; faça o loop manualmente com `retrieve()` em CLI/worker. Veja o exemplo em [Polling](README.md#polling-manual-na-v30) no README.

## Verificação de assinatura de webhook

O SDK v2 não fornecia helper de assinatura. Integradores rolavam o seu próprio (veja, por exemplo, o `nfeio-whmcs-modulo`).

A v3 traz `Nfe\Webhook`:

```php
use Nfe\Webhook;

$event = Webhook::constructEvent(
    payload:   file_get_contents('php://input'),
    sigHeader: $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '',
    secret:    $webhookSecret,
);
```

Algoritmo: HMAC-SHA1 sobre o corpo bruto da requisição, em hex, com prefixo `sha1=<hex>` no header `X-Hub-Signature`. Esquema canônico confirmado com a API NFE.io em 2026-05-13.

## Tipos gerados

A v2 tinha classes ad-hoc derivadas de `NFe_Object` por entidade. A v3 gera DTOs a partir dos specs OpenAPI em `openapi/` para `src/Generated/`. **Nunca edite esses arquivos à mão.**

```php
$invoice = $nfe->serviceInvoices->retrieve($companyId, $invoiceId);
// $invoice é Nfe\Resource\Dto\ServiceInvoices\ServiceInvoice
// (hand-written, porque nf-servico-v1.yaml não tem schemas)

$company = $nfe->companies->retrieve($companyId);
// $company é um DTO gerado em Nfe\Generated\...
```

A maioria das famílias usa os DTOs gerados em `src/Generated/`. Algumas famílias (NFS-e e variantes onde o spec OpenAPI é Swagger 2.0 ou não tem schemas) usam DTOs hand-written em `src/Resource/Dto/<Família>/`. O tipo de retorno declarado em cada método do recurso é a fonte da verdade.

## Helper de polling, Idempotency-Key e outras features adiadas

- `pollUntilComplete()` — adiado para uma release 3.x futura. Faça loop manual usando `retrieve()` + `FlowStatus::isTerminal()`.
- Header `Idempotency-Key` — a API da NFE.io **não suporta** hoje (confirmado em 2026-05-13). O SDK não expõe slot para ele. Quando a API adicionar suporte, uma minor release aditiva incluirá o campo em `RequestOptions`.

## Exemplos detalhados de migração

> _A ser preenchido como parte do trabalho de release tooling: um exemplo
> vanilla, um exemplo Laravel e o padrão do módulo WHMCS (`nfeio-whmcs-modulo`)._
