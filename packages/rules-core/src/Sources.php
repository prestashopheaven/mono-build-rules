<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    prestahaven.com
 * @copyright Since 2026 George van Engers
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace PrestaShopHeaven\Build\RulesCore;

/**
 * Finds the rules JSON files of every rules package, wherever the command runs: in the monorepo
 * (packages/rules-*), in a module (vendor/prestashopheaven/build-rules-*) or in the workspace
 * (libraries/mono-build-rules/packages/rules-*). The first root that has rules wins, so a module uses the rules of the
 * version it requires.
 */
final class Sources
{
    /** The places of the rules files below a root, in order. */
    public const PATTERNS = [
        '/packages/rules-*/rules/*.json',
        '/vendor/prestashopheaven/build-rules-*/rules/*.json',
        '/libraries/mono-build-rules/packages/rules-*/rules/*.json',
    ];

    /**
     * The rules files below the first of $roots (or a folder above it) that has any, sorted by path.
     *
     * @param list<string> $roots
     *
     * @return list<string>
     *
     * @throws \RuntimeException when no root has rules files
     */
    public static function files(array $roots): array
    {
        foreach ($roots as $root) {
            $dir = rtrim(str_replace('\\', '/', (string) (realpath($root) ?: $root)), '/');
            for ($i = 0; $i < 8 && $dir !== '' && $dir !== dirname($dir); ++$i, $dir = dirname($dir)) {
                foreach (self::PATTERNS as $pattern) {
                    $files = glob($dir . $pattern) ?: [];
                    if ($files !== []) {
                        sort($files);

                        return $files;
                    }
                }
            }
        }

        throw new \RuntimeException('No rules packages found (packages/rules-*, vendor/prestashopheaven/build-rules-* or libraries/mono-build-rules).');
    }

    /**
     * The preamble of conventions.md, shipped in this package.
     *
     * @throws \RuntimeException when the file is missing
     */
    public static function preamble(?string $file = null): string
    {
        $file ??= dirname(__DIR__) . '/resources/preamble.md';
        $text = is_file($file) ? file_get_contents($file) : false;
        if ($text === false) {
            throw new \RuntimeException("The preamble $file is missing.");
        }

        return $text;
    }

    /**
     * The registry of the first root with rules.
     *
     * @param list<string> $roots
     *
     * @throws \RuntimeException when there are no rules files or one cannot be read
     * @throws \InvalidArgumentException when the rules are wrong (see Registry::fromFiles())
     */
    public static function registry(array $roots, ?string $preamble = null): Registry
    {
        return Registry::fromFiles(self::files($roots), self::preamble($preamble));
    }
}
