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
use PrestaShopHeaven\Build\RulesCore\Cli;
use PrestaShopHeaven\Build\RulesCore\Registry;
use PrestaShopHeaven\Build\RulesCore\Rule;
use PrestaShopHeaven\Build\RulesCore\Section;

/**
 * Every command of rules.php, its output, exit code and errors.
 */
final class CliTest extends TestCase
{
    /**
     * Runs the command on a small registry; returns [exit code, stdout, stderr].
     *
     * @param list<string> $args
     * @param (callable(): Registry)|null $registry
     *
     * @return array{0: int, 1: string, 2: string}
     */
    private function runCli(array $args, ?callable $registry = null): array
    {
        $out = fopen('php://memory', 'w+');
        $err = fopen('php://memory', 'w+');
        self::assertIsResource($out);
        self::assertIsResource($err);
        $code = Cli::run($args, $registry ?? self::registry(...), $out, $err);
        rewind($out);
        rewind($err);

        return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err)];
    }

    /**
     * Two sections: STR (a build rule with the validator, a design review rule) and PAR (a rule for all stages).
     */
    private static function registry(): Registry
    {
        return Registry::fromSections([
            Section::fromArray(Fixtures::section('1', [
                Fixtures::rule('STR-01'),
                Fixtures::rule('STR-02', ['marker' => '', 'stages' => ['design'], 'checks' => ['review'], 'markdown' => '- **STR-02** ' . str_repeat('long words ', 10)]),
            ]), 'str.json'),
            Section::fromArray(Fixtures::section('25', [Fixtures::rule('PAR-01', ['marker' => '', 'stages' => ['all'], 'checks' => ['review'], 'markdown' => '- **PAR-01** One session per repo.'])], ['PAR']), 'par.json'),
        ], "# Conventions\n");
    }

    public function testValidateReportsTheCounts(): void
    {
        [$code, $out] = $this->runCli(['validate']);

        self::assertSame(0, $code);
        self::assertSame("Rules: 3\nStages: all 1, build 1, design 1\nChecks: review 2, validator 1\n", $out);
    }

    public function testListShowsOneLinePerRuleAndFilters(): void
    {
        [$code, $out] = $this->runCli(['list']);
        self::assertSame(0, $code);
        self::assertCount(3, explode("\n", trim($out, " \n\r\t\v\0")));
        self::assertStringContainsString('STR-01   build                  validator          The technical name is the folder name.', $out);
        self::assertStringContainsString('…', $out);

        [, $design] = $this->runCli(['list', '--stage=design']);
        self::assertSame(['STR-02', 'PAR-01'], array_map(static fn (string $line): string => substr($line, 0, 6), explode("\n", trim($design, " \n\r\t\v\0"))));

        [, $validator] = $this->runCli(['list', '--check=validator']);
        self::assertStringStartsWith('STR-01', $validator);
        self::assertCount(1, explode("\n", trim($validator, " \n\r\t\v\0")));
    }

    public function testShowPrintsTheMarkdown(): void
    {
        [$code, $out] = $this->runCli(['show', 'PAR-01']);

        self::assertSame(0, $code);
        self::assertSame("- **PAR-01** One session per repo.\n", $out);
    }

    public function testShowOfAnUnknownRuleFails(): void
    {
        [$code, , $err] = $this->runCli(['show', 'XYZ-01']);

        self::assertSame(1, $code);
        self::assertSame("There is no rule XYZ-01.\n", $err);
    }

    public function testStagePrintsTheChecklistUnderSectionHeadings(): void
    {
        [$code, $out] = $this->runCli(['stage', 'build']);

        self::assertSame(0, $code);
        self::assertSame("# Rules for the stage \"build\"\n\n## 1. Section 1 (STR)\n\n" . Fixtures::rule()['markdown'] . "\n\n## 25. Section 25 (PAR)\n\n- **PAR-01** One session per repo.\n\n(2 rules)\n", $out);
    }

    public function testStageReviewOnlyLeavesOutToolAndValidatorRules(): void
    {
        [, $out] = $this->runCli(['stage', 'build', '--review-only']);

        self::assertStringStartsWith("# Rules for the stage \"build\" (review only)\n\n## 25.", $out);
        self::assertStringEndsWith("(1 rules)\n", $out);
    }

    public function testStageOfAnUnknownStageFails(): void
    {
        [$code, , $err] = $this->runCli(['stage', 'deploy']);

        self::assertSame(1, $code);
        self::assertStringContainsString('unknown stage "deploy"', $err);
    }

    public function testGenerateWritesAndChecksConventions(): void
    {
        $file = Fixtures::tempDir() . '/conventions.md';

        [$missing, , $err] = $this->runCli(['generate', "--out=$file", '--check']);
        self::assertSame(1, $missing);
        self::assertStringContainsString('differs from the rules registry', $err);

        [$code, $out] = $this->runCli(['generate', "--out=$file"]);
        self::assertSame(0, $code);
        self::assertSame("Wrote $file\n", $out);
        self::assertSame(self::registry()->conventions(), file_get_contents($file));

        file_put_contents($file, str_replace("\n", "\r\n", (string) file_get_contents($file)));
        [$same, $sameOut] = $this->runCli(['generate', "--out=$file", '--check']);
        self::assertSame(0, $same);
        self::assertSame("$file matches the rules registry.\n", $sameOut);

        file_put_contents($file, 'changed by hand');
        [$differs] = $this->runCli(['generate', "--out=$file", '--check']);
        self::assertSame(1, $differs);
    }

    public function testGenerateToAFolderThatDoesNotExistFails(): void
    {
        [$code, , $err] = @$this->runCli(['generate', '--out=' . Fixtures::tempDir() . '/no/such/folder/conventions.md']);

        self::assertSame(1, $code);
        self::assertStringStartsWith('Cannot write', $err);
    }

    public function testWrongUsageExitsWithTwoWithoutLoadingRules(): void
    {
        $never = static function (): Registry {
            throw new \LogicException('The registry is not needed for a usage error.');
        };
        foreach ([[], ['nope'], ['show'], ['stage'], ['generate']] as $args) {
            [$code, , $err] = $this->runCli($args, $never);
            self::assertSame(2, $code, implode(' ', $args));
            self::assertSame(Cli::USAGE . "\n", $err);
        }
    }

    public function testBrokenRulesFailWithTheirMessage(): void
    {
        [$code, , $err] = $this->runCli(['validate'], static function (): Registry {
            throw new \RuntimeException('No rules packages found.');
        });

        self::assertSame(1, $code);
        self::assertSame("No rules packages found.\n", $err);
    }

    public function testSummaryCutsTheMarkerAndLongText(): void
    {
        self::assertSame('The technical name is the folder name.', Cli::summary(Rule::fromArray(Fixtures::rule())));
        self::assertSame(70, mb_strlen(Cli::summary(Rule::fromArray(Fixtures::rule('STR-02', ['markdown' => '- **STR-02** ' . str_repeat('x', 100)])))));
    }

    public function testOptionsSplitFlagsValuesAndPositionals(): void
    {
        self::assertSame([['stage' => 'build', 'review-only' => '1'], ['list', 'x']], Cli::options(['list', '--stage=build', '--review-only', 'x']));
    }
}
