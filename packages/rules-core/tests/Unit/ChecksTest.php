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

use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Build\RulesCore\Checks;

/**
 * A rule is checked by the validator, a (named) tool step or a reviewer.
 */
final class ChecksTest extends TestCase
{
    public function testTheThreeKindsAndEveryNamedToolStepAreKnown(): void
    {
        self::assertTrue(Checks::isKnown('validator'));
        self::assertTrue(Checks::isKnown('tool'));
        self::assertTrue(Checks::isKnown('review'));
        foreach (Checks::TOOL_STEPS as $step) {
            self::assertTrue(Checks::isKnown("tool:$step"));
        }
    }

    public function testUnknownChecksAreRefused(): void
    {
        self::assertFalse(Checks::isKnown('tool:'));
        self::assertFalse(Checks::isKnown('tool:magic'));
        self::assertFalse(Checks::isKnown('phpstan'));
        self::assertFalse(Checks::isKnown(''));
    }

    public function testAssertKnownPassesAKnownCheck(): void
    {
        Checks::assertKnown('tool:phpstan', 'test');
        $this->addToAssertionCount(1);
    }

    public function testAssertKnownNamesTheContext(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule Y: unknown check "magic"');

        Checks::assertKnown('magic', 'Rule Y');
    }
}
