#!/usr/bin/env bash
#
# Gate de lint: roda `php -l` em todo arquivo PHP de src/ (inclusive
# src/Generated/) com error_reporting=E_ALL e falha se algum arquivo tiver
# erro ou emitir qualquer diagnóstico (Deprecated, Warning, Fatal error...).
#
# Ao contrário do lint do gerador (token_get_all), `php -l` compila o arquivo,
# então pega erros de compilação como case de enum duplicado e deprecations
# de assinatura (parâmetro opcional antes de obrigatório).
#
# Uso:
#   scripts/lint.sh            # lint de src/
#   scripts/lint.sh <dir>      # lint de outro diretório
#   LINT_JOBS=8 scripts/lint.sh
#
# Em Docker: docker compose run --rm -T php composer lint

set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
target="${1:-$root/src}"
jobs="${LINT_JOBS:-4}"

if [ ! -d "$target" ]; then
    echo "✗ Diretório não encontrado: $target" >&2
    exit 1
fi

total=$(find "$target" -type f -name '*.php' | wc -l | tr -d ' ')
if [ "$total" -eq 0 ]; then
    echo "✗ Nenhum arquivo PHP em $target" >&2
    exit 1
fi

# Cada `php -l` imprime "No syntax errors detected in <arquivo>" quando o
# arquivo está limpo. Qualquer outra linha não vazia é um problema. O exit
# code do `php -l` é descartado de propósito: a saída é a fonte da verdade,
# porque deprecations não mudam o exit code, e um fatal (status 255) faria o
# xargs abortar antes de varrer os demais arquivos.
output=$(
    find "$target" -type f -name '*.php' -print0 \
        | xargs -0 -n 1 -P "$jobs" sh -c \
            'php -d error_reporting=E_ALL -d display_errors=1 -d log_errors=0 -l "$1" 2>&1; exit 0' lint
)

clean=$(printf '%s\n' "$output" | grep -c '^No syntax errors detected in ' || true)
problems=$(printf '%s\n' "$output" | grep -v -e '^No syntax errors detected in ' -e '^[[:space:]]*$' || true)

if [ -n "$problems" ] || [ "$clean" -ne "$total" ]; then
    echo "✗ php -l reprovou $((total - clean)) arquivo(s) com erro e emitiu os diagnósticos abaixo ($total analisados):" >&2
    printf '%s\n' "$problems" >&2
    exit 1
fi

echo "✓ php -l: $total arquivo(s) sem erros nem deprecations em ${target#"$root"/}"
