#!/usr/bin/env bash
# Cut a release: bump Version.php, mover seção [Unreleased] do CHANGELOG,
# criar tag anotada, push opcional. Idempotente; falha cedo se algo estiver
# inconsistente. Use --dry-run para ver o que aconteceria sem efeitos colaterais.
#
# Uso:
#   scripts/release.sh                        # interativo
#   scripts/release.sh --version 3.0.0-rc.1
#   scripts/release.sh --version 3.0.0-rc.1 --dry-run
#   scripts/release.sh --version 3.0.0 --skip-tests
#
# Pre-condições:
#  - branch atual = v3
#  - working tree limpo
#  - CI verde no último commit (verificado via gh quando disponível)
#  - tag pretendida ainda não existe

set -euo pipefail

DRY_RUN=0
SKIP_TESTS=0
SKIP_GIT=0
VERSION=""

usage() {
    sed -n '2,15p' "$0"
    exit 0
}

while [ $# -gt 0 ]; do
    case "$1" in
        --version) VERSION="$2"; shift 2 ;;
        --dry-run) DRY_RUN=1; shift ;;
        --skip-tests) SKIP_TESTS=1; shift ;;
        --skip-git) SKIP_GIT=1; shift ;;
        -h|--help) usage ;;
        *) echo "Argumento desconhecido: $1" >&2; usage ;;
    esac
done

echo() { command echo -e "$@"; }
info() { echo "\033[1;36m▸\033[0m $1"; }
ok()   { echo "\033[1;32m✓\033[0m $1"; }
fail() { echo "\033[1;31m✗\033[0m $1"; exit 1; }
run()  { if [ $DRY_RUN -eq 1 ]; then echo "  \033[1;33mdry-run:\033[0m $*"; else eval "$@"; fi; }

# -- Pre-flight ---------------------------------------------------------------

info "Pre-flight checks"

current_branch=$(git symbolic-ref --short HEAD)
if [ "$current_branch" != "v3" ]; then
    fail "Branch atual é '$current_branch'. Releases saem da 'v3'."
fi
ok "branch = v3"

if ! git diff-index --quiet HEAD --; then
    fail "Working tree sujo. Faça commit ou stash antes."
fi
ok "working tree limpo"

if command -v gh >/dev/null 2>&1; then
    last_run=$(gh run list --branch v3 --workflow ci.yml --limit 1 --json conclusion --jq '.[0].conclusion' 2>/dev/null || echo "")
    case "$last_run" in
        success) ok "CI verde no último commit" ;;
        "")      info "(gh CLI presente mas não foi possível confirmar CI — siga com cuidado)" ;;
        *)       fail "Último CI: $last_run. Resolva antes de cortar release." ;;
    esac
else
    info "(gh CLI ausente — pulando verificação de CI)"
fi

# -- Versão -------------------------------------------------------------------

if [ -z "$VERSION" ]; then
    echo ""
    read -rp "Versão a lançar (ex.: 3.0.0-rc.1, 3.0.0): " VERSION
fi

if ! echo "$VERSION" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-(rc|beta|alpha)\.[0-9]+)?$'; then
    fail "Formato inválido: '$VERSION'. Use X.Y.Z ou X.Y.Z-(rc|beta|alpha).N."
fi
ok "versão = $VERSION"

if git rev-parse "v$VERSION" >/dev/null 2>&1; then
    fail "Tag v$VERSION já existe. Aborte."
fi

# -- Atualizar Version.php ----------------------------------------------------

info "Atualiza src/Version.php"
if [ $DRY_RUN -eq 1 ]; then
    echo "  \033[1;33mdry-run:\033[0m sed CURRENT = '$VERSION' em src/Version.php"
else
    if ! grep -q "public const CURRENT = " src/Version.php; then
        fail "Padrão 'public const CURRENT = ' não encontrado em src/Version.php"
    fi
    sed -i.bak -E "s/public const CURRENT = '[^']+';/public const CURRENT = '$VERSION';/" src/Version.php
    rm -f src/Version.php.bak
    grep "public const CURRENT" src/Version.php
fi

# -- Atualizar CHANGELOG ------------------------------------------------------

info "Move seção [Unreleased] do CHANGELOG para [$VERSION]"
today=$(date -u +%Y-%m-%d)

if [ $DRY_RUN -eq 1 ]; then
    echo "  \033[1;33mdry-run:\033[0m substituir '## [Unreleased]' por '## [$VERSION] — $today' e prepender nova [Unreleased] vazia"
else
    # Inserir [Unreleased] novo acima do antigo, depois renomear o antigo.
    # Usa Perl para portabilidade entre GNU/BSD sed.
    perl -i -pe "
        if (\$_ =~ /^## \[Unreleased\]/ && !\$done) {
            \$_ = \"## [Unreleased]\\n\\n## [$VERSION] — $today\\n\";
            \$done = 1;
        }
    " CHANGELOG.md
    head -15 CHANGELOG.md
fi

# -- Tests --------------------------------------------------------------------

if [ $SKIP_TESTS -eq 0 ]; then
    info "Rodando suite de testes"
    if [ $DRY_RUN -eq 1 ]; then
        echo "  \033[1;33mdry-run:\033[0m composer test"
    else
        if command -v docker >/dev/null 2>&1 && [ -f docker-compose.yml ]; then
            docker compose run --rm --quiet-pull php vendor/bin/pest --colors=never
        else
            composer test
        fi
    fi
fi

# -- Git: commit + tag --------------------------------------------------------

if [ $SKIP_GIT -eq 0 ]; then
    info "Commit + tag"
    run "git add src/Version.php CHANGELOG.md"
    run "git commit -m 'chore(release): v$VERSION'"

    # Mensagem da tag = bloco [VERSION] do CHANGELOG
    if [ $DRY_RUN -eq 0 ]; then
        notes=$(awk "/^## \[$VERSION\]/,/^## \[/" CHANGELOG.md | sed '$d')
        printf '%s\n' "$notes" | git tag -a "v$VERSION" -F -
    else
        echo "  \033[1;33mdry-run:\033[0m git tag -a v$VERSION (mensagem extraída do CHANGELOG)"
    fi

    info "Próximos passos (manual):"
    echo "  git push origin v3"
    echo "  git push origin v$VERSION"
    echo ""
    echo "Quando a tag chegar no remoto, o workflow .github/workflows/release.yml"
    echo "verifica matrix + cria GitHub Release automaticamente."
fi

ok "Release $VERSION preparada"
