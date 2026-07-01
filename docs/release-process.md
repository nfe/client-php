# Processo de Release v3

Runbook completo para cortar uma release da branch `v3`. Use a primeira vez para `v3.0.0-rc.1`; revise nas próximas releases.

## Pré-requisitos uma única vez (admin do repo + Packagist)

### 1. Packagist — pacote `nfe/nfe` (já existe)

A v3 **reusa o mesmo pacote da v2**: [`nfe/nfe`](https://packagist.org/packages/nfe/nfe). Não há novo pacote a submeter. O Packagist já indexa as majors 1, 2 e 3 sob o mesmo slug; o Composer resolve cada constraint (`^2.0`, `^3.0`) para a major correta.

Confirmado em 2026-06-30: a tag `v3.0.0-rc.1` foi indexada automaticamente sob `nfe/nfe` assim que chegou no remoto.

### 2. Webhook GitHub → Packagist (já configurado)

O webhook do repo `nfe/client-php` (GitHub) aponta para o pacote `nfe/nfe` no Packagist e dispara em cada `git push --tags`, atualizando em ≤ 5 min. Configuração para referência:

1. Repo `nfe/client-php` no GitHub → `Settings → Webhooks`
2. Payload URL: `https://packagist.org/api/github?username=<user-packagist>`
3. Content type: `application/json`
4. Secret: o API token do Packagist (`Profile → Show API token`)
5. Eventos: **Push**
6. Active: ✓

Validar: após pushar uma tag, checar `https://packagist.org/packages/nfe/nfe` — a nova versão deve aparecer em minutos.

### 3. Configurar secrets para o workflow de integration

`.github/workflows/integration.yml` roda contra a API real. Precisa em `Settings → Secrets → Actions`:

| Secret | Conteúdo |
|---|---|
| `NFE_SDK_E2E_API_KEY` | chave principal de sandbox NFE.io |
| `NFE_SDK_E2E_DATA_API_KEY` | (opcional) chave de serviços de dados |
| `NFE_SDK_E2E_COMPANY_ID` | id de uma empresa cadastrada no sandbox |
| `NFE_SDK_E2E_WEBHOOK_SECRET` | (opcional, só para o teste de webhook) |

---

## Cortar uma release

### Para cada release

**Antes de cortar (manual):** revise a skill de agente `skills/nfeio-php-sdk` contra `src/`
— se a superfície pública de algum resource mudou (métodos, assinaturas, tipos de retorno,
paginação, erros), atualize o `SKILL.md` e as `references/`. A skill é empacotada pelo mesmo
`git archive` da tag, então uma skill defasada seria publicada junto com o release.

A operação é **um comando** depois dos pré-requisitos:

```bash
# 1. dry-run primeiro (sem efeito colateral)
scripts/release.sh --version 3.0.0-rc.1 --dry-run

# 2. vai pra valer (cria commit local + tag local; NÃO pusha)
scripts/release.sh --version 3.0.0-rc.1
```

O script faz, em ordem:
1. Pre-flight: confere branch=`v3`, working tree limpo, CI verde via `gh`, tag pretendida ainda não existe.
2. Atualiza `src/Version.php` com `'3.0.0-rc.1'`.
3. Move a seção `[Unreleased]` do `CHANGELOG.md` para `[3.0.0-rc.1] — YYYY-MM-DD` e prepende uma nova `[Unreleased]` vazia.
4. Roda `composer test`.
5. Cria commit `chore(release): v3.0.0-rc.1`.
6. Cria tag anotada `v3.0.0-rc.1` com as notas do CHANGELOG como mensagem.

### Push manual

O script para antes do push **intencionalmente** — você revisa o commit + tag local antes de empurrar:

```bash
git log -1
git show v3.0.0-rc.1
```

**Recomendado antes do push**: rode a integration suite manualmente para validar que o sandbox NFE.io responde como esperado. É o único momento em que vale gastar cota da API.

```bash
gh workflow run integration.yml --ref v3 -f reason="pre-release v3.0.0-rc.1"
gh run watch  # aguarda concluir
```

```bash
# tudo certo? então pusha
git push origin v3
git push origin v3.0.0-rc.1
```

Quando a tag chega no remote, dois automatismos disparam:

- `.github/workflows/release.yml` valida matrix completa (PHP 8.2/8.3/8.4 + PHPStan + CS + OpenAPI sync + consistência tag↔Version.php) e cria GitHub Release. Tags `-rc/-beta/-alpha` viram prerelease automaticamente.
- Webhook GitHub → Packagist publica a versão em ~5min.

### Validação pós-release

```bash
# Tag publicada no GitHub Release?
gh release view v3.0.0-rc.1

# Packagist indexou? (map de versões -> commit)
curl -s https://repo.packagist.org/p2/nfe/nfe.json | jq '.packages."nfe/nfe" | map(.version)'

# Instalação real funciona?
mkdir -p /tmp/install-test && cd /tmp/install-test
composer init --no-interaction --name=test/install --require="nfe/nfe:3.0.0-rc.1" --stability=RC
composer install --quiet
php -r "require 'vendor/autoload.php'; echo Nfe\\Version::CURRENT . PHP_EOL;"
```

---

## Cadência

| Tipo | Janela mínima de RC/beta |
|---|---|
| Patch (`x.y.Z`) | sem RC, direto após CI verde |
| Minor (`x.Y.0`) | 7 dias (14 se feature substancial) |
| Major (`X.0.0`) | 14 dias mínimo |

Para a primeira release `v3.0.0`:

1. **Hoje**: cortar `v3.0.0-rc.1`. Comunicar internamente (Teams) + para integradores conhecidos (módulo WHMCS, etc.).
2. **Dia 7**: cortar `v3.0.0-rc.2` se houver fix; senão pular.
3. **Dia 14**: se zero issues críticas, cortar `v3.0.0` GA.
4. **Pós-GA**: trocar badge "v3-em-desenvolvimento" no README por badge de versão estável; remover banner da branch `master`.

---

## Rollback / desfazer release local

Se rodou `release.sh` mas ainda **não pushou**:

```bash
# Remove a tag local
git tag -d v3.0.0-rc.1

# Reverte o commit de release
git reset --hard HEAD^
```

Se já pushou tag mas precisa retirar (situação rara — apenas se algo crítico foi descoberto entre push e indexação):

```bash
# CUIDADO: força delete no remote
git push --delete origin v3.0.0-rc.1
gh release delete v3.0.0-rc.1 --yes
```

Nunca delete uma tag que já foi instalada por terceiros via Packagist — isso quebra builds. Em vez disso, publique uma nova patch (`v3.0.0-rc.2`).
