<?php

declare(strict_types=1);

namespace Nfe\Build;

use CompileError;

/**
 * Syntactic gate over the generated tree: every emitted file must parse.
 *
 * Runs in-process via token_get_all(TOKEN_PARSE) — no `php -l` spawn per file.
 * This turns the whole class of "invalid PHP shipped in the dist" bugs
 * (e.g., unsanitised dotted type-hints) into a build failure.
 */
final class Linter
{
    /**
     * @param array<string, string> $files Map of relative path => PHP source.
     * @return list<string> Relative paths of files that fail to parse, with the parse error appended.
     */
    public static function lint(array $files): array
    {
        $errors = [];

        foreach ($files as $rel => $source) {
            try {
                token_get_all($source, TOKEN_PARSE);
            } catch (CompileError $e) {
                $errors[] = "{$rel}: {$e->getMessage()}";
            }
        }

        return $errors;
    }
}
