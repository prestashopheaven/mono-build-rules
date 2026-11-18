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
 * Every rule of the Module-Factory: the sections of all rules packages, in the order of their numbers, with the
 * preamble of conventions.md. Rule IDs are unique over all packages, and a prefix belongs to one section only.
 */
final class Registry
{
    /**
     * A registry of sorted, checked sections; use fromFiles() or fromSections().
     *
     * @param list<Section> $sections
     */
    private function __construct(public readonly string $preamble, public readonly array $sections)
    {
    }

    /**
     * The registry from rules JSON files and the preamble.
     *
     * @param list<string> $files
     *
     * @throws \RuntimeException when a file cannot be read or holds no JSON object
     * @throws \InvalidArgumentException when a section or rule is wrong, a rule ID or section number is used twice, or
     *                                   two sections claim the same prefix
     */
    public static function fromFiles(array $files, string $preamble): self
    {
        $sections = [];
        foreach ($files as $file) {
            $json = is_file($file) ? file_get_contents($file) : false;
            $data = $json === false ? null : json_decode($json, true);
            if (!is_array($data)) {
                throw new \RuntimeException("$file: cannot be read as a JSON object.");
            }
            $sections[] = Section::fromArray($data, basename($file));
        }

        return self::fromSections($sections, $preamble);
    }

    /**
     * The registry from sections; they are sorted by number ("5" before "5b" before "6").
     *
     * @param list<Section> $sections
     *
     * @throws \InvalidArgumentException when a rule ID or section number is used twice, or two sections claim a prefix
     */
    public static function fromSections(array $sections, string $preamble): self
    {
        usort($sections, static fn (Section $a, Section $b): int => strnatcmp($a->number, $b->number));
        $ids = [];
        $owners = [];
        $numbers = [];
        foreach ($sections as $section) {
            if (isset($numbers[$section->number])) {
                throw new \InvalidArgumentException("Section number {$section->number} is used twice.");
            }
            $numbers[$section->number] = true;
            foreach ($section->prefixes as $prefix) {
                if (isset($owners[$prefix])) {
                    throw new \InvalidArgumentException("Prefix $prefix belongs to sections {$owners[$prefix]} and {$section->number}.");
                }
                $owners[$prefix] = $section->number;
            }
            foreach ($section->rules() as $rule) {
                if (isset($ids[$rule->id])) {
                    throw new \InvalidArgumentException("Rule {$rule->id} exists twice.");
                }
                $ids[$rule->id] = true;
            }
        }

        return new self($preamble, $sections);
    }

    /**
     * Every rule, in document order.
     *
     * @return list<Rule>
     */
    public function rules(): array
    {
        $rules = [];
        foreach ($this->sections as $section) {
            array_push($rules, ...$section->rules());
        }

        return $rules;
    }

    /**
     * The rule with $id.
     *
     * @throws \InvalidArgumentException when there is no such rule
     */
    public function rule(string $id): Rule
    {
        foreach ($this->rules() as $rule) {
            if ($rule->id === $id) {
                return $rule;
            }
        }

        throw new \InvalidArgumentException("There is no rule $id.");
    }

    /**
     * The rules that apply in $stage, in document order.
     *
     * @return list<Rule>
     *
     * @throws \InvalidArgumentException when $stage is unknown
     */
    public function forStage(string $stage): array
    {
        Stages::assertKnown($stage, 'forStage');

        return array_values(array_filter($this->rules(), static fn (Rule $rule): bool => $rule->appliesIn($stage)));
    }

    /**
     * conventions.md as people read it: the preamble, then every section.
     */
    public function conventions(): string
    {
        $parts = [$this->preamble];
        foreach ($this->sections as $section) {
            $parts[] = $section->markdown();
        }

        return implode("\n", $parts);
    }

    /**
     * The number of rules per stage and per check, for the gate of the registry itself.
     *
     * @return array{rules: int, stages: array<string, int>, checks: array<string, int>}
     */
    public function stats(): array
    {
        $stats = ['rules' => 0, 'stages' => [], 'checks' => []];
        foreach ($this->rules() as $rule) {
            ++$stats['rules'];
            foreach ($rule->stages as $stage) {
                $stats['stages'][$stage] = ($stats['stages'][$stage] ?? 0) + 1;
            }
            foreach ($rule->checks as $check) {
                $stats['checks'][$check] = ($stats['checks'][$check] ?? 0) + 1;
            }
        }
        ksort($stats['stages']);
        ksort($stats['checks']);

        return $stats;
    }
}
