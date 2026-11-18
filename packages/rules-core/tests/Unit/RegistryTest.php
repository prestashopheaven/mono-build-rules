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
use PrestaShopHeaven\Build\RulesCore\Registry;
use PrestaShopHeaven\Build\RulesCore\Rule;
use PrestaShopHeaven\Build\RulesCore\Section;

/**
 * The registry orders the sections, keeps IDs and prefixes unique, filters per stage and regenerates conventions.md.
 */
final class RegistryTest extends TestCase
{
    public function testSectionsAreOrderedNaturallyAndConventionsAreRebuiltAsWritten(): void
    {
        $registry = Registry::fromSections([
            Section::fromArray(Fixtures::section('6', [Fixtures::rule('HK-01', ['markdown' => '- **HK-01 [V]** Hooks.'])], ['HK']), 'hk.json'),
            Section::fromArray(Fixtures::section('5b', [Fixtures::rule('DOC-01', ['markdown' => '- **DOC-01 [V]** Docs.'])], ['DOC']), 'doc.json'),
            Section::fromArray(Fixtures::section('5', [Fixtures::rule('PHP-01', ['markdown' => '- **PHP-01 [V]** PHP.'])], ['PHP']), 'php.json'),
        ], "# Conventions\n");

        self::assertSame(['5', '5b', '6'], array_map(static fn (Section $section): string => $section->number, $registry->sections));
        self::assertSame(['PHP-01', 'DOC-01', 'HK-01'], array_map(static fn (Rule $rule): string => $rule->id, $registry->rules()));
        self::assertStringStartsWith("# Conventions\n\n## 5. Section 5 (PHP)\n\n- **PHP-01 [V]** PHP.\n", $registry->conventions());
        self::assertSame('DOC-01', $registry->rule('DOC-01')->id);
    }

    public function testForStageKeepsRulesOfThatStageAndOfAll(): void
    {
        $registry = Registry::fromSections([Section::fromArray(Fixtures::section('1', [
            Fixtures::rule('STR-01'),
            Fixtures::rule('STR-02', ['stages' => ['design'], 'markdown' => '- **STR-02** x']),
            Fixtures::rule('STR-03', ['stages' => ['all'], 'markdown' => '- **STR-03** x']),
        ]), 'str.json')], '');

        self::assertSame(['STR-01', 'STR-03'], array_map(static fn (Rule $rule): string => $rule->id, $registry->forStage('build')));
        self::assertSame(['STR-02', 'STR-03'], array_map(static fn (Rule $rule): string => $rule->id, $registry->forStage('design')));
    }

    public function testStatsCountRulesPerStageAndCheck(): void
    {
        $registry = Registry::fromSections([Section::fromArray(Fixtures::section('1', [
            Fixtures::rule('STR-01'),
            Fixtures::rule('STR-02', ['stages' => ['design', 'build'], 'checks' => ['review'], 'markdown' => '- **STR-02** x']),
        ]), 'str.json')], '');

        self::assertSame(['rules' => 2, 'stages' => ['build' => 2, 'design' => 1], 'checks' => ['review' => 1, 'validator' => 1]], $registry->stats());
    }

    public function testFromFilesReadsJsonFiles(): void
    {
        $dir = Fixtures::tempDir();
        Fixtures::writeJson("$dir/str.json", Fixtures::section());

        $registry = Registry::fromFiles(["$dir/str.json"], 'pre');

        self::assertSame('pre', $registry->preamble);
        self::assertCount(1, $registry->rules());
    }

    public function testFromFilesRefusesAMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot be read as a JSON object');

        Registry::fromFiles([Fixtures::tempDir() . '/missing.json'], '');
    }

    public function testFromFilesRefusesAFileWithoutAJsonObject(): void
    {
        $dir = Fixtures::tempDir();
        file_put_contents("$dir/broken.json", '"text"');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('broken.json: cannot be read as a JSON object');

        Registry::fromFiles(["$dir/broken.json"], '');
    }

    public function testARuleIdTwiceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rule STR-01 exists twice.');

        Registry::fromSections([Section::fromArray(Fixtures::section('1', [Fixtures::rule(), Fixtures::rule()]), 'str.json')], '');
    }

    public function testASectionNumberTwiceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Section number 1 is used twice.');

        Registry::fromSections([
            Section::fromArray(Fixtures::section('1'), 'a.json'),
            Section::fromArray(Fixtures::section('1', [Fixtures::rule('MOD-01', ['markdown' => '- **MOD-01** x'])], ['MOD']), 'b.json'),
        ], '');
    }

    public function testAPrefixInTwoSectionsIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefix STR belongs to sections 1 and 2.');

        Registry::fromSections([
            Section::fromArray(Fixtures::section('1'), 'a.json'),
            Section::fromArray(Fixtures::section('2', [Fixtures::rule('STR-02', ['markdown' => '- **STR-02** x'])]), 'b.json'),
        ], '');
    }

    public function testAnUnknownRuleIsRefused(): void
    {
        $registry = Registry::fromSections([Section::fromArray(Fixtures::section(), 'str.json')], '');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no rule XYZ-01.');

        $registry->rule('XYZ-01');
    }

    public function testForStageRefusesAnUnknownStage(): void
    {
        $registry = Registry::fromSections([Section::fromArray(Fixtures::section(), 'str.json')], '');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unknown stage "deploy"');

        $registry->forStage('deploy');
    }
}
