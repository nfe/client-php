---
title: Consulta de CNPJ (legalEntityLookup) no SDK PHP da NFE.io
sidebar_label: Consulta de CNPJ
sidebar_position: 11
slug: consulta-cnpj
description: Consulte dados cadastrais de pessoa jurídica por CNPJ e a inscrição estadual por UF com $nfe->legalEntityLookup no host legalentity.api.nfe.io.
---

# Consulta de CNPJ (`legalEntityLookup`)

`$nfe->legalEntityLookup` consulta dados cadastrais de **pessoa jurídica** por
CNPJ, incluindo a inscrição estadual (IE) por UF. Recurso **global** (não recebe
`$companyId`), servido por `legalentity.api.nfe.io`.

:::note Família de dados — usa `dataApiKey`
Com fallback para `apiKey`. Um 403 com chave principal válida normalmente
significa que o plano não inclui o produto de dados. Veja
[Configuração](../configuration.md).
:::

## Métodos

| Método | Descrição | Retorno |
|---|---|---|
| `getBasicInfo($cnpj, $opts = null)` | Dados cadastrais básicos da Receita. | `LegalEntityResponse` |
| `getStateTaxInfo($state, $cnpj)` | Inscrição estadual na UF. | `LegalEntityResponse` |
| `getStateTaxForInvoice($state, $cnpj)` | IE apta para emissão de NF-e. | `LegalEntityResponse` |
| `getSuggestedStateTaxForInvoice($state, $cnpj)` | IE sugerida para a NF-e. | `LegalEntityResponse` |

## Consultar os dados básicos

```php
$resposta = $nfe->legalEntityLookup->getBasicInfo('11.444.555/0001-49');

$pj = $resposta->legalEntity;      // array com os dados desembrulhados (ou null)
$pj['name'] ?? null;
$pj['address'] ?? null;

$resposta->raw;                    // payload completo original
```

`getBasicInfo()` aceita um segundo argumento com parâmetros de query — por
exemplo, `['updateAddress' => false, 'updateCityCode' => true]`.

## Consultar a inscrição estadual por UF

```php
$ie = $nfe->legalEntityLookup->getStateTaxInfo('SP', '11444555000149');

// Variantes voltadas à emissão de NF-e:
$nfe->legalEntityLookup->getStateTaxForInvoice('SP', '11444555000149');
$nfe->legalEntityLookup->getSuggestedStateTaxForInvoice('SP', '11444555000149');
```

:::warning UF é validada localmente
`$state` é normalizada para maiúsculas; uma UF desconhecida lança
`Nfe\Exception\InvalidRequestException` antes de qualquer requisição.
:::

:::tip Enriquecendo cadastros
Um fluxo comum: consultar o CNPJ aqui e usar o resultado para pré-preencher o
cadastro do tomador em [`legalPeople`](./legal-people.md) antes de emitir a
nota.
:::

## Veja também

- [Consulta de CPF](./natural-person-lookup.md) — o equivalente PF.
- [Inscrições estaduais](./state-taxes.md) — IEs da **sua** empresa emissora.
- [Configuração](../configuration.md) — modelo de duas chaves.
