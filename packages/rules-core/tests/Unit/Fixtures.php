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

namespace PrestaShopHeaven\Build\RulesCore\Tests\Unit;

/**
 * Rule entries, sections and temporary rules folders for the unit tests.
 */
final class Fixtures
{
    /**
     * A valid rule entry; $overrides replace fields.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function rule(string $id = 'STR-01', array $overrides = []): array
    {
        return array_merge([
            'type' => 'rule',
            'id' => $id,
            'marker' => 'V',
            'stages' => ['build'],
            'checks' => ['validator'],
            'markdown' => "- **$id [V]** The technical name is the folder name.\n  A second line.",
        ], $overrides);
    }

    /**
     * A valid section with a text block and the given rules.
     *
     * @param list<array<string, mixed>> $rules
     * @param list<string> $prefixes
     *
     * @return array<string, mixed>
     */
    public static function section(string $number = '1', array $rules = [], array $prefixes = ['STR']): array
    {
        return [
            'number' => $number,
            'title' => "Section $number",
            'prefixes' => $prefixes,
            'blocks' => array_merge([['type' => 'text', 'markdown' => '']], $rules === [] ? [self::rule()] : $rules, [['type' => 'text', 'markdown' => "\n---\n"]]),
        ];
    }

    /**
     * A new empty temporary folder.
     */
    public static function tempDir(): string
    {
        $dir = sys_get_temp_dir() . '/rules-core-' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        return str_replace('\\', '/', $dir);
    }

    /**
     * Writes $data as JSON to $file, creating its folder.
     *
     * @param array<string, mixed> $data
     */
    public static function writeJson(string $file, array $data): void
    {
        @mkdir(dirname($file), 0777, true);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
