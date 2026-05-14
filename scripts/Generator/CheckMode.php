<?php

declare(strict_types=1);

namespace Nfe\Build;

/**
 * Compare what the generator *would* emit against what is committed under src/Generated.
 *
 * Used by the CI job `openapi-sync` to fail PRs that touch openapi/* without
 * regenerating types.
 */
final class CheckMode
{
    /**
     * @return array{ok: bool, added: list<string>, removed: list<string>, changed: list<string>}
     */
    public static function diff(Generator $generator, string $outputRoot): array
    {
        $expected = $generator->generate();
        $actual   = self::scanDir($outputRoot);

        $added   = array_values(array_diff(array_keys($expected), array_keys($actual)));
        $removed = array_values(array_diff(array_keys($actual), array_keys($expected)));
        $changed = [];

        foreach ($expected as $rel => $contents) {
            if (isset($actual[$rel]) && $actual[$rel] !== $contents) {
                $changed[] = $rel;
            }
        }

        sort($added);
        sort($removed);
        sort($changed);

        return [
            'ok'      => $added === [] && $removed === [] && $changed === [],
            'added'   => $added,
            'removed' => $removed,
            'changed' => $changed,
        ];
    }

    /**
     * @return array<string, string> relative path => contents
     */
    private static function scanDir(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $stack = [$root];
        while ($stack !== []) {
            $current = array_pop($stack);
            foreach (scandir($current) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $abs = $current . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($abs)) {
                    $stack[] = $abs;
                    continue;
                }
                if (!str_ends_with($entry, '.php')) {
                    continue;
                }
                $rel = substr($abs, strlen($root) + 1);
                $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
                $contents = file_get_contents($abs);
                if ($contents !== false) {
                    $files[$rel] = $contents;
                }
            }
        }

        return $files;
    }
}
