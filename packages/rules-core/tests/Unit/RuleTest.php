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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrestaShopHeaven\Build\RulesCore\Rule;

/**
 * A rule entry is read strictly: every field is checked, and a valid entry survives a round trip.
 */
final class RuleTest extends TestCase
{
    public function testAValidEntryRoundTrips(): void
    {
        $entry = Fixtures::rule('ARCH-03', ['marker' => 'T', 'stages' => ['design', 'build'], 'checks' => ['tool:architecture'], 'markdown' => '- **ARCH-03 [T]** Domain uses nothing.']);
        $rule = Rule::fromArray($entry);

        self::assertSame($entry, $rule->toArray());
        self::assertSame('ARCH', $rule->prefix());
    }

    public function testAppliesInItsStagesAndAllAppliesEverywhere(): void
    {
        $rule = Rule::fromArray(Fixtures::rule());
        self::assertTrue($rule->appliesIn('build'));
        self::assertFalse($rule->appliesIn('design'));

        $everywhere = Rule::fromArray(Fixtures::rule('PAR-01', ['stages' => ['all'], 'markdown' => '- **PAR-01** x']));
        self::assertTrue($everywhere->appliesIn('intake'));
        self::assertTrue($everywhere->appliesIn('retro'));
    }

    public function testReviewOnlyMeansNoToolOrValidator(): void
    {
        self::assertTrue(Rule::fromArray(Fixtures::rule('STR-05', ['marker' => '', 'checks' => ['review'], 'markdown' => '- **STR-05** x']))->isReviewOnly());
        self::assertFalse(Rule::fromArray(Fixtures::rule())->isReviewOnly());
        self::assertFalse(Rule::fromArray(Fixtures::rule('STR-06', ['checks' => ['review', 'tool:conventions'], 'markdown' => '- **STR-06 [V]** x']))->isReviewOnly());
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function wrongEntries(): iterable
    {
        yield 'missing id' => [['id' => null], 'needs an id'];
        yield 'id without dash' => [['id' => 'STR01'], 'needs an id'];
        yield 'lower-case id' => [['id' => 'str-01'], 'needs an id'];
        yield 'one digit' => [['id' => 'STR-1'], 'needs an id'];
        yield 'unknown marker' => [['marker' => 'X'], 'marker must be'];
        yield 'stages not a list' => [['stages' => 'build'], 'stages must be a non-empty list'];
        yield 'empty stages' => [['stages' => []], 'stages must be a non-empty list'];
        yield 'stages as a map' => [['stages' => ['a' => 'build']], 'stages must be a non-empty list'];
        yield 'empty stage' => [['stages' => [' ']], 'only hold non-empty strings'];
        yield 'stage not a string' => [['stages' => [3]], 'only hold non-empty strings'];
        yield 'stage twice' => [['stages' => ['build', 'build']], 'holds a value twice'];
        yield 'unknown stage' => [['stages' => ['deploy']], 'unknown stage "deploy"'];
        yield 'missing checks' => [['checks' => null], 'checks must be a non-empty list'];
        yield 'unknown check' => [['checks' => ['magic']], 'unknown check "magic"'];
        yield 'unknown tool step' => [['checks' => ['tool:magic']], 'unknown check "tool:magic"'];
        yield 'markdown missing' => [['markdown' => null], 'markdown must start with'];
        yield 'markdown of another rule' => [['markdown' => '- **STR-02** x'], 'markdown must start with "- **STR-01"'];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('wrongEntries')]
    public function testAWrongEntryIsRefused(array $override, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Rule::fromArray(array_merge(Fixtures::rule(), $override));
    }
}
