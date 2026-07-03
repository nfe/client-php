---
title: Empresas (companies) no SDK PHP da NFE.io
sidebar_label: Empresas
sidebar_position: 6
slug: empresas
description: CRUD de empresas emissoras e leitura do status do certificado digital no recurso companies do SDK PHP da NFE.io — escopo de conta, remove() e listAll().
---

# Empresas (`companies`)

O recurso `$nfe->companies` gerencia as empresas emissoras da sua conta — CRUD
completo mais a **leitura** do status do certificado digital. Tem escopo de
**conta** (não recebe `$companyId` como primeiro argumento), faz parte da
família `main` servida por `api.nfe.io` (`/v1`) e usa a `apiKey`.

:::note Exclusão é `remove()`, não `delete()`
Para excluir uma empresa, chame `companies->remove($companyId)` — retorna um
`array{deleted, id}`. O nome mantém paridade com os SDKs Node e Ruby.
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `create($data)` | Cria uma empresa emissora. | `Company` |
| `list($options = [])` | Lista paginada (`pageIndex` **1-based**). | `ListResponse` |
| `listAll()` | Todas as empresas (auto-paginação client-side). | `array<Company>` |
| `retrieve($companyId)` | Consulta por id. | `Company` |
| `update($companyId, $data)` | Atualiza. | `Company` |
| `remove($companyId)` | Exclui. | `array{deleted, id}` |
| `findByTaxNumber($taxNumber)` | Busca por CNPJ/CPF (varredura client-side). | `?Company` |
| `findByName($name)` | Busca por nome (varredura client-side). | `array<Company>` |
| `getCertificateStatus($companyId, $expiringSoonThreshold = 30)` | Status do certificado digital. | `CertificateStatus` |
| `checkCertificateExpiration($companyId, $thresholdDays = 30)` | Alerta de expiração. | `array` |
| `getCompaniesWithCertificates()` | Empresas com certificado instalado. | `array<Company>` |
| `getCompaniesWithExpiringCertificates($thresholdDays = 30)` | Empresas com certificado a vencer. | `array<Company>` |

:::warning Upload de certificado não está no SDK
O transporte do SDK é JSON-only (sem multipart) — **não** há
`uploadCertificate()`/`validateCertificate()` na `v3.0`. Faça o upload do
certificado `.pfx` pelo painel [app.nfe.io](https://app.nfe.io) (ou pela API
REST diretamente); use o SDK para **ler** o status e monitorar a expiração.
:::

## Exemplos

### Criar e recuperar uma empresa

```php
$company = $nfe->companies->create([
    'name'             => 'Acme Serviços LTDA',
    'federalTaxNumber' => 12345678000199,
    'email'            => 'fiscal@acme.com.br',
]);

$nfe->companies->retrieve($company->id);
```

:::note `federalTaxNumber` é `int` para empresas
No DTO `Company` (e `LegalPerson`), `federalTaxNumber` é inteiro; em
`NaturalPerson` é string. O SDK não valida nem coage no create — envie o tipo
que a API espera.
:::

### Paginar e localizar empresas

```php
// Uma página por vez (pageIndex é 1-based):
$page = $nfe->companies->list(['pageIndex' => 1, 'pageCount' => 50]);
foreach ($page->data as $c) {
    echo $c->name, PHP_EOL;
}

// Todas as empresas (auto-paginação client-side):
$todas = $nfe->companies->listAll();

// Buscas auxiliares (filtragem no cliente):
$acme = $nfe->companies->findByTaxNumber('12.345.678/0001-99');
$semelhantes = $nfe->companies->findByName('acme');
```

:::warning Helpers de busca não são otimizados
`findByTaxNumber`, `findByName`, `listAll`, `getCompaniesWithCertificates` e
`getCompaniesWithExpiringCertificates` percorrem **todas** as páginas e filtram
no cliente. Evite em contas com muitas empresas.
:::

### Monitorar o certificado digital

```php
$status = $nfe->companies->getCertificateStatus($company->id);
$status->daysUntilExpiration;   // calculado client-side a partir de expiresOn
$status->isExpiringSoon;        // threshold padrão: 30 dias

$alerta = $nfe->companies->checkCertificateExpiration($company->id, thresholdDays: 15);

// Varredura da conta inteira:
$vencendo = $nfe->companies->getCompaniesWithExpiringCertificates(thresholdDays: 30);
```

## Veja também

- [Pessoas jurídicas](./legal-people.md) e [Pessoas físicas](./natural-people.md) — tomadores vinculados à empresa.
- [Webhooks](./webhooks.md) — eventos por empresa.
- [Tratamento de erros](../errors.md).
