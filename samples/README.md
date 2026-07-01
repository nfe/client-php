# Exemplos runnable

Scripts PHP que exercitam o SDK contra a API real da NFE.io. Útil para
avaliar antes de instalar via Composer ou para reproduzir cenários de
suporte/diagnóstico.

## Setup

```bash
cp samples/.env.example samples/.env
# preencha NFE_API_KEY, NFE_COMPANY_ID, e opcionalmente
# NFE_DATA_API_KEY e NFE_WEBHOOK_SECRET
```

Depois rode qualquer script:

```bash
# Diagnóstico geral (recomendado primeiro)
php samples/test-connection.php

# Consultas read-only
php samples/cep-lookup.php 01310-100
php samples/cnpj-lookup.php 33000167000101
php samples/cpf-lookup.php 12345678901 1990-01-15

# Empresas
php samples/companies-list.php

# NFS-e
php samples/service-invoice-list.php 20
php samples/service-invoice-issue.php
php samples/service-invoice-download.php <INVOICE_ID> /tmp

# Webhook (servidor local + endpoint)
php -S 0.0.0.0:8000 samples/webhook-verify.php
```

## O que é e o que não é

- **Não são testes.** Os asserts ficam em `tests/`. Estes scripts são
  receitas humanas — falham se a API estiver fora ou as credenciais
  inválidas, e isso é proposital.
- **Não persistem nada.** Os exemplos read-only não criam recursos.
  O único exemplo que cria recurso real é `service-invoice-issue.php`,
  e ele assume ambiente Sandbox/Development na empresa de teste.
- **Bootstrap compartilhado.** Todos os scripts dão `require __DIR__ . '/_bootstrap.php'`
  para carregar `.env` e instanciar `Nfe\Client`. O `_bootstrap.php` retorna
  o cliente já configurado.

## Adicionando novos exemplos

1. Use `_bootstrap.php` em vez de instanciar o cliente do zero.
2. Comente o "Uso:" no topo do arquivo. Esses comentários são a documentação primária.
3. Trate erros típicos (`AuthorizationException`, `NotFoundException`) com mensagens humanas.
4. Liste o novo script em `samples/README.md`.
