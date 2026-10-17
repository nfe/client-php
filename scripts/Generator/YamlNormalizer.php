<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Re-indents the continuation lines of multi-line quoted scalars that sit
 * at or left of their key's column, so symfony/yaml can parse them.
 *
 * The upstream specs carry descriptions like:
 *
 *     description: "### Informações adicionais\r\n⛔ **Indisponível...
 *
 *     | `type` | tpEvento | Uso |
 *     ..."
 *
 * with continuation lines at column 0. Lenient parsers (PyYAML/libyaml,
 * js-yaml) accept it; YAML 1.2 requires those lines to be indented past the
 * parent node and symfony/yaml rejects them with "Malformed inline YAML
 * string". Inside a quoted scalar the leading white space of a continuation
 * line is never content (it is folded away), so indenting such a line
 * further does not change the parsed value — it only makes the document
 * well-formed.
 *
 * Scope is deliberately narrow: only a quoted scalar that opens as the value
 * of a block mapping key or block sequence entry and does not close on that
 * line is touched, and only its continuation lines whose indentation is at
 * or left of the key/entry column. Block scalars (`|`, `>`) and comments are
 * skipped so their content is never rewritten. {@see SpecLoader} only calls
 * this after symfony/yaml has already rejected the raw document.
 */
final class YamlNormalizer
{
    /**
     * Matches the start of a block node line and captures the column where
     * its value begins:
     *   [1] indentation plus any `- ` sequence markers
     *   [2] optional implicit key (plain, single- or double-quoted) and `:`
     *   [3] the value itself (rest of the line)
     */
    private const NODE_LINE = '/^((?:[ ]*-(?:[ ]+|$))*[ ]*)'
        . '((?:"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\']|\'\')*\'|[^\s#"\'\-?:,\[\]{}&*!|>%@`][^#]*?|-[^\s#][^#]*?)[ ]*:(?:[ ]+|$))?'
        . '(.*)$/';

    private const BLOCK_SCALAR = '/^[|>][1-9+\-]*[ ]*(?:#.*)?$/';

    /**
     * @return array{0: string, 1: int} The normalised document and how many lines were re-indented.
     */
    public static function reindentQuotedContinuations(string $yaml): array
    {
        $lines = preg_split('/(?<=\n)/', $yaml) ?: [];
        $out = [];
        $changed = 0;

        $blockIndent = null; // inside a block scalar opened at this indentation
        $quote = null;       // inside a quoted scalar opened with this character
        $column = 0;         // column of the key/entry that owns the open scalar

        foreach ($lines as $raw) {
            $line = rtrim($raw, "\r\n");
            $eol  = substr($raw, strlen($line));

            if ($blockIndent !== null) {
                if (trim($line) === '' || self::indent($line) > $blockIndent) {
                    $out[] = $raw;
                    continue;
                }
                $blockIndent = null;
            }

            if ($quote !== null) {
                if (trim($line) !== '' && self::indent($line) <= $column) {
                    $line = str_repeat(' ', $column + 2) . ltrim($line, " \t");
                    $changed++;
                }
                if (self::closingQuote($line, 0, $quote) !== null) {
                    $quote = null;
                }
                $out[] = $line . $eol;
                continue;
            }

            $out[] = $raw;

            $trimmed = ltrim($line, ' ');
            if ($trimmed === '' || $trimmed[0] === '#' || preg_match(self::NODE_LINE, $line, $m) !== 1) {
                continue;
            }

            // A value only counts when it belongs to a key or a `- ` entry: a bare
            // line starting with a quote may be the continuation of a plain scalar.
            $hasKey = $m[2] !== '';
            if (!$hasKey && !str_contains($m[1], '-')) {
                continue;
            }

            $value = $m[3];
            if (preg_match(self::BLOCK_SCALAR, $value) === 1) {
                // Content belongs to the block while indented past the key column
                // (or past the entry's own indentation for `- |`).
                $blockIndent = $hasKey ? strlen($m[1]) : self::indent($line);
                continue;
            }

            $opening = $value[0] ?? '';
            if ($opening !== '"' && $opening !== "'") {
                continue;
            }

            $valueOffset = strlen($m[1]) + strlen($m[2]);
            if (self::closingQuote($line, $valueOffset + 1, $opening) === null) {
                $quote  = $opening;
                $column = strlen($m[1]);
            }
        }

        return [implode('', $out), $changed];
    }

    private static function indent(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }

    /**
     * Offset of the quote that closes a scalar opened with $quote, scanning
     * from $from; null when the scalar continues on the next line.
     * Double-quoted scalars escape with a backslash, single-quoted with `''`.
     */
    private static function closingQuote(string $line, int $from, string $quote): ?int
    {
        $length = strlen($line);
        for ($i = $from; $i < $length; $i++) {
            $char = $line[$i];
            if ($quote === '"' && $char === '\\') {
                $i++;
                continue;
            }
            if ($char !== $quote) {
                continue;
            }
            if ($quote === "'" && ($line[$i + 1] ?? '') === "'") {
                $i++;
                continue;
            }
            return $i;
        }

        return null;
    }
}
