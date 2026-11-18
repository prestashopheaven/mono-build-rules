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
 * The commands of bin/rules.php (routed from ps-module rules):
 *
 *   validate                          load every rule and report the counts per stage and check
 *   list [--stage=S] [--check=C]      one line per rule: ID, stages, checks, first words
 *   show <ID>                         the rule as Markdown
 *   stage <stage> [--review-only]     the checklist of a stage: every rule that applies, under its section heading
 *   generate --out=FILE [--check]     write conventions.md; with --check only compare and fail when it differs
 *
 * Exit code 0 = ok, 1 = failed (wrong rules, file differs), 2 = wrong usage.
 */
final class Cli
{
    /** The usage line. */
    public const USAGE = 'Usage: rules.php validate | list [--stage=S] [--check=C] | show <ID> | stage <stage> [--review-only] | generate --out=FILE [--check]';

    /**
     * Runs a command on $registry (built lazily, so usage errors need no rules); writes to $out and $err.
     *
     * @param list<string> $args the arguments without the script name
     * @param callable(): Registry $registry
     * @param resource $out
     * @param resource $err
     */
    public static function run(array $args, callable $registry, $out, $err): int
    {
        [$options, $rest] = self::options($args);
        try {
            switch ($rest[0] ?? '') {
                case 'validate':
                    $stats = $registry()->stats();
                    fwrite($out, "Rules: {$stats['rules']}\n" . self::counts('Stages', $stats['stages']) . self::counts('Checks', $stats['checks']));

                    return 0;
                case 'list':
                    foreach ($registry()->rules() as $rule) {
                        if (isset($options['stage']) && !$rule->appliesIn($options['stage'])) {
                            continue;
                        }
                        if (isset($options['check']) && !in_array($options['check'], $rule->checks, true)) {
                            continue;
                        }
                        fwrite($out, sprintf("%-8s %-22s %-18s %s\n", $rule->id, implode(',', $rule->stages), implode(',', $rule->checks), self::summary($rule)));
                    }

                    return 0;
                case 'show':
                    if (!isset($rest[1])) {
                        throw new \InvalidArgumentException(self::USAGE);
                    }
                    fwrite($out, $registry()->rule($rest[1])->markdown . "\n");

                    return 0;
                case 'stage':
                    if (!isset($rest[1])) {
                        throw new \InvalidArgumentException(self::USAGE);
                    }
                    fwrite($out, self::checklist($registry(), $rest[1], isset($options['review-only'])));

                    return 0;
                case 'generate':
                    if (!isset($options['out'])) {
                        throw new \InvalidArgumentException(self::USAGE);
                    }
                    $text = $registry()->conventions();
                    if (isset($options['check'])) {
                        $current = is_file($options['out']) ? str_replace("\r\n", "\n", (string) file_get_contents($options['out'])) : null;
                        if ($current !== $text) {
                            fwrite($err, "{$options['out']} differs from the rules registry: run rules.php generate --out={$options['out']}.\n");

                            return 1;
                        }
                        fwrite($out, "{$options['out']} matches the rules registry.\n");

                        return 0;
                    }
                    if (file_put_contents($options['out'], $text) === false) {
                        throw new \RuntimeException("Cannot write {$options['out']}.");
                    }
                    fwrite($out, "Wrote {$options['out']}\n");

                    return 0;
                default:
                    throw new \InvalidArgumentException(self::USAGE);
            }
        } catch (\InvalidArgumentException $e) {
            fwrite($err, $e->getMessage() . "\n");

            return $e->getMessage() === self::USAGE ? 2 : 1;
        } catch (\RuntimeException $e) {
            fwrite($err, $e->getMessage() . "\n");

            return 1;
        }
    }

    /**
     * The checklist of $stage: a heading, then per section with rules for the stage its heading and those rules.
     *
     * @throws \InvalidArgumentException when $stage is unknown
     */
    public static function checklist(Registry $registry, string $stage, bool $reviewOnly): string
    {
        Stages::assertKnown($stage, 'stage');
        $text = "# Rules for the stage \"$stage\"" . ($reviewOnly ? ' (review only)' : '') . "\n";
        $count = 0;
        foreach ($registry->sections as $section) {
            $rules = array_filter($section->rules(), static fn (Rule $rule): bool => $rule->appliesIn($stage) && (!$reviewOnly || $rule->isReviewOnly()));
            if ($rules === []) {
                continue;
            }
            $text .= "\n" . $section->heading() . "\n\n";
            foreach ($rules as $rule) {
                $text .= $rule->markdown . "\n";
                ++$count;
            }
        }

        return $text . "\n($count rules)\n";
    }

    /**
     * The first words of a rule, without the list marker and ID: at most 70 characters on one line.
     */
    public static function summary(Rule $rule): string
    {
        $first = (string) preg_replace('/^- \*\*[A-Z]{2,5}-\d+(?: \[[VT]\])?\*\*\s*/', '', strtok($rule->markdown, "\n") ?: '');

        return mb_strlen($first) > 70 ? rtrim(mb_substr($first, 0, 69), " \n\r\t\v\0") . '…' : $first;
    }

    /**
     * Splits arguments into options (--name=value, --flag => '1') and positional arguments.
     *
     * @param list<string> $args
     *
     * @return array{0: array<string, string>, 1: list<string>}
     */
    public static function options(array $args): array
    {
        $options = [];
        $rest = [];
        foreach ($args as $arg) {
            if (preg_match('/^--([a-z0-9-]+)(?:=(.*))?$/s', $arg, $m)) {
                $options[$m[1]] = $m[2] ?? '1';
            } else {
                $rest[] = $arg;
            }
        }

        return [$options, $rest];
    }

    /**
     * One line "Label: a 3, b 2".
     *
     * @param array<string, int> $counts
     */
    private static function counts(string $label, array $counts): string
    {
        $parts = [];
        foreach ($counts as $name => $count) {
            $parts[] = "$name $count";
        }

        return "$label: " . implode(', ', $parts) . "\n";
    }
}
