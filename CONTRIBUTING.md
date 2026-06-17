# Contributing

## Branches

- **`v3`** — active development for the v3 rewrite. All new work happens here.
- **`master`** — frozen at v2.5. Receives no further updates.

PRs targeting `master` will be closed; open them against `v3` instead.

## Local setup

You have two equivalent paths. Pick whichever fits your workflow.

### Option A — Docker (recommended, zero local PHP)

No PHP or Composer on the host required.

```bash
git clone https://github.com/nfe/client-php.git
cd client-php
git checkout v3

cp .env.docker.example .env       # captures your UID/GID for file ownership
make build                        # builds php82/php83/php84 images (~2 min, once)
make install                      # composer install inside the container
make test                         # pest on PHP 8.2
make test-matrix                  # pest on 8.2, 8.3, AND 8.4
make generate                     # regenerate src/Generated/ from openapi/*.yaml
make shell                        # bash into the PHP 8.2 container
```

See [Makefile](Makefile) for the full list of targets. Each one is a thin
`docker compose run` wrapper. Override the active PHP version with
`make test PHP=php83`.

### Option B — Native PHP

```bash
git clone https://github.com/nfe/client-php.git
cd client-php
git checkout v3
composer install
```

Requires PHP 8.2, 8.3, or 8.4 and Composer 2 on the host.

## Toolchain

| Tool | Purpose | Command |
|---|---|---|
| Pest | Test runner | `composer test` |
| PHPStan | Static analysis (level 8) | `composer stan` |
| PHP-CS-Fixer | Style checks (`@PER-CS2.0` + `@PHP82Migration` + strict types) | `composer cs` |
| PHP-CS-Fixer (auto-fix) | Apply style fixes | `composer cs:fix` |
| OpenAPI codegen | Regenerate `src/Generated/` from `openapi/*.yaml` | `composer generate` |
| OpenAPI sync check | Fail if `src/Generated/` is out of sync with specs | `composer generate:check` |

All four are enforced by CI (`.github/workflows/ci.yml`) on every push and PR to `v3`.

## Conventions

- **PHP**: `declare(strict_types=1);` in every file under `src/`, `tests/`, and `scripts/`.
- **Namespacing**: PSR-4 with `Nfe\` rooted at `src/`. File path mirrors namespace.
- **Code style**: PER-CS 2.0 + PHP 8.2 migration ruleset. Run `composer cs:fix` before committing.
- **No new runtime dependencies** without a dedicated OpenSpec change discussing the tradeoff. `require-dev` packages are unrestricted but should justify their weight.

## Updating OpenAPI specs

NFE.io maintains the OpenAPI specs externally. To bring an updated spec into the SDK:

1. Replace the relevant file under `openapi/` (e.g., `openapi/service-invoice-rtc-v1.yaml`).
2. Run `composer generate` — this rewrites the affected subdirectory of `src/Generated/`.
3. Commit both the spec file and the regenerated `src/Generated/` files in the same PR.
4. CI will fail your PR with a clear diff if step 2 was skipped.

Never edit files under `src/Generated/` by hand. They begin with a `// AUTO-GENERATED` marker.

## OpenSpec workflow

This repository uses [OpenSpec](https://github.com/Fission-AI/OpenSpec) to plan substantial changes. Active proposals live under `openspec/changes/`. To inspect:

```bash
openspec list
openspec show <change-name>
openspec validate <change-name>
```

If you are unsure whether your change needs a proposal, lean toward yes — the
proposal is also where design decisions are captured for future readers.

## Commit messages

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(http): add retry policy with exponential backoff
fix(webhook): accept signatures without algo= prefix
docs(readme): document discriminated 202 response
chore(deps): bump phpstan to 2.1
```

## Release cadence

The v3 line follows semver strictly:

| Tipo | Janela mínima de RC/beta | Notas |
|---|---|---|
| Patch (`x.y.Z`) | sem RC | corta direto após CI verde + revisão |
| Minor (`x.Y.0`) | 7 dias de RC se feature pequena; 14 se feature substancial | tag `vX.Y.0-rc.N` |
| Major (`X.0.0`) | 14 dias de RC mínimo | tag `vX.0.0-rc.N`, anunciar interno |

### Cutting a release

Use o script interativo:

```bash
# dry-run primeiro para ver o que aconteceria
scripts/release.sh --version 3.0.0-rc.1 --dry-run

# vai pra valer
scripts/release.sh --version 3.0.0-rc.1
```

O script faz pre-flight (branch correta, working tree limpo, CI verde via `gh`),
bumpa `src/Version.php`, move a seção `[Unreleased]` do CHANGELOG para a versão
nomeada, roda os testes, faz commit `chore(release): vX.Y.Z` e cria a tag
anotada com a mensagem extraída do CHANGELOG. **O push é manual** — você
revisa antes:

```bash
git push origin v3
git push origin v3.0.0-rc.1
```

Quando a tag chega no remoto, `.github/workflows/release.yml` valida a matrix
(8.2/8.3/8.4 + PHPStan + CS + OpenAPI sync + consistência Version.php↔tag) e
cria a GitHub Release com as notas do CHANGELOG. Tags `vX.Y.Z-(rc|beta|alpha).N`
são publicadas como prerelease.

## Reporting issues

Open an issue at https://github.com/nfe/client-php/issues. For security-sensitive
issues, email suporte@nfe.io.
