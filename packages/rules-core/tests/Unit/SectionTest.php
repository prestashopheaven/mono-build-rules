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
use PrestaShopHeaven\Build\RulesCore\Section;

/**
 * A section keeps its blocks in order, renders them back as written, and refuses wrong content.
 */
final class SectionTest extends TestCase
{
    public function testASectionRendersItsHeadingAndBlocksInOrder(): void
    {
        $data = Fixtures::section('5b', [Fixtures::rule('DOC-01', ['markdown' => '- **DOC-01 [V]** Docblocks.']), Fixtures::rule('MKT-02', ['markdown' => '- **MKT-02 [V]** Texts.'])], ['DOC', 'MKT']);
        $section = Section::fromArray($data, 'doc.json');

        self::assertSame("## 5b. Section 5b (DOC, MKT)\n\n- **DOC-01 [V]** Docblocks.\n- **MKT-02 [V]** Texts.\n\n---\n", $section->markdown());
        self::assertSame(['DOC-01', 'MKT-02'], array_map(static fn (Rule $rule): string => $rule->id, $section->rules()));
        self::assertSame($data, $section->toArray());
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function wrongSections(): iterable
    {
        yield 'number missing' => [['number' => null], 'number must be like'];
        yield 'number with a dot' => [['number' => '5.1'], 'number must be like'];
        yield 'title empty' => [['title' => ' '], 'title must be a non-empty string'];
        yield 'prefixes missing' => [['prefixes' => null], 'prefixes must be a non-empty list'];
        yield 'prefixes empty' => [['prefixes' => []], 'prefixes must be a non-empty list'];
        yield 'prefixes as a map' => [['prefixes' => ['a' => 'STR']], 'prefixes must be a non-empty list'];
        yield 'lower-case prefix' => [['prefixes' => ['str']], 'a prefix must be 2 to 5 capitals'];
        yield 'blocks missing' => [['blocks' => null], 'blocks must be a list'];
        yield 'block of unknown type' => [['blocks' => [['type' => 'table']]], 'block 0 must be'];
        yield 'text block without markdown' => [['blocks' => [['type' => 'text']]], 'block 0 must be'];
        yield 'block not an object' => [['blocks' => ['text']], 'block 0 must be'];
        yield 'rule of another section' => [['blocks' => [Fixtures::rule('MOD-01', ['markdown' => '- **MOD-01 [V]** x'])]], 'rule MOD-01 does not belong to this section (STR)'];
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('wrongSections')]
    public function testAWrongSectionIsRefusedNamingTheFile(array $override, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Section::fromArray(array_merge(Fixtures::section(), $override), 'str.json');
    }

    public function testAWrongRuleInsideIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule STR-01: unknown stage "deploy"');

        Section::fromArray(Fixtures::section('1', [Fixtures::rule('STR-01', ['stages' => ['deploy']])]), 'str.json');
    }
}
