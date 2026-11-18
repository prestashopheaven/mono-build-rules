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
use PrestaShopHeaven\Build\RulesCore\Stages;

/**
 * The stages are the pipeline's, in order, plus "all".
 */
final class StagesTest extends TestCase
{
    public function testEveryPipelineStageAndAllAreKnown(): void
    {
        foreach (Stages::ORDER as $stage) {
            self::assertTrue(Stages::isKnown($stage));
        }
        self::assertTrue(Stages::isKnown('all'));
        self::assertFalse(Stages::isKnown('deploy'));
        self::assertFalse(Stages::isKnown(''));
        self::assertSame('intake', Stages::ORDER[0]);
    }

    public function testAssertKnownPassesAKnownStage(): void
    {
        Stages::assertKnown('verify', 'test');
        $this->addToAssertionCount(1);
    }

    public function testAssertKnownNamesTheContextAndTheKnownStages(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule X: unknown stage "deploy" (known: intake, research');

        Stages::assertKnown('deploy', 'Rule X');
    }
}
