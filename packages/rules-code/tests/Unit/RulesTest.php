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

namespace PrestaShopHeaven\Build\RulesCode\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Build\RulesCore\Registry;
use PrestaShopHeaven\Build\RulesCore\Rule;

/**
 * Every rules file of this package is a valid section (rules-core reads it strictly), and every rule belongs to one
 * of the areas this package owns.
 */
final class RulesTest extends TestCase
{
    /** The rule prefixes this package owns. */
    private const PREFIXES = ['ARCH', 'CMP', 'DB', 'DEP', 'DOC', 'HK', 'INS', 'MOD', 'PHP', 'PKG', 'SEC', 'STR'];

    public function testEveryRulesFileLoadsAndOwnsItsRules(): void
    {
        $files = glob(dirname(__DIR__, 2) . '/rules/*.json') ?: [];
        self::assertNotEmpty($files);

        $registry = Registry::fromFiles($files, '');

        self::assertNotEmpty($registry->rules());
        foreach ($registry->rules() as $rule) {
            self::assertContains($rule->prefix(), self::PREFIXES, $rule->id);
        }
    }
}
