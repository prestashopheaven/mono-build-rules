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
 * One rule of the Module-Factory: its ID (STR-03), the marker of the old conventions ([V] validator, [T] tool, none =
 * review), the stages of the pipeline where it applies, how it is checked, and its text as Markdown (the list item of
 * conventions.md, including its continuation lines).
 */
final class Rule
{
    /** The pattern of a rule ID: 2 to 5 capitals, a dash, two or more digits. */
    public const ID_PATTERN = '/^[A-Z]{2,5}-\d{2,}$/';

    /**
     * A checked rule; use fromArray().
     *
     * @param list<string> $stages
     * @param list<string> $checks
     */
    private function __construct(
        public readonly string $id,
        public readonly string $marker,
        public readonly array $stages,
        public readonly array $checks,
        public readonly string $markdown,
    ) {
    }

    /**
     * A rule from its registry entry (a block of type "rule" in a rules JSON file).
     *
     * @param array<mixed> $data
     *
     * @throws \InvalidArgumentException when a field is missing or wrong (unknown stage or check, bad ID or marker,
     *                                   empty text, or a text that does not start with the rule's own ID)
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        if (!is_string($id) || !preg_match(self::ID_PATTERN, $id)) {
            throw new \InvalidArgumentException('A rule needs an id like STR-03, got ' . var_export($id, true) . '.');
        }
        $marker = $data['marker'] ?? '';
        if (!in_array($marker, ['', 'V', 'T'], true)) {
            throw new \InvalidArgumentException("Rule $id: marker must be \"\", \"V\" or \"T\".");
        }
        $stages = self::stringList($data['stages'] ?? null, "Rule $id: stages");
        foreach ($stages as $stage) {
            Stages::assertKnown($stage, "Rule $id");
        }
        $checks = self::stringList($data['checks'] ?? null, "Rule $id: checks");
        foreach ($checks as $check) {
            Checks::assertKnown($check, "Rule $id");
        }
        $markdown = $data['markdown'] ?? null;
        if (!is_string($markdown) || !str_starts_with($markdown, "- **$id")) {
            throw new \InvalidArgumentException("Rule $id: markdown must start with \"- **$id\".");
        }

        return new self($id, $marker, $stages, $checks, $markdown);
    }

    /**
     * The prefix of the ID: the area of the rule (STR for STR-03).
     */
    public function prefix(): string
    {
        return explode('-', $this->id)[0];
    }

    /**
     * Whether the rule applies in $stage (a rule for "all" applies in every stage).
     */
    public function appliesIn(string $stage): bool
    {
        return in_array($stage, $this->stages, true) || in_array(Stages::ALL, $this->stages, true);
    }

    /**
     * Whether only a reviewer (a person or the reviewer agent) can judge the rule: no tool or validator checks it.
     */
    public function isReviewOnly(): bool
    {
        return $this->checks === [Checks::REVIEW];
    }

    /**
     * The registry entry of the rule (the inverse of fromArray()).
     *
     * @return array{type: string, id: string, marker: string, stages: list<string>, checks: list<string>, markdown: string}
     */
    public function toArray(): array
    {
        return [
            'type' => 'rule',
            'id' => $this->id,
            'marker' => $this->marker,
            'stages' => $this->stages,
            'checks' => $this->checks,
            'markdown' => $this->markdown,
        ];
    }

    /**
     * A non-empty list of unique non-empty strings.
     *
     * @return list<string>
     *
     * @throws \InvalidArgumentException when $value is not such a list
     */
    private static function stringList(mixed $value, string $what): array
    {
        if (!is_array($value) || $value === [] || !array_is_list($value)) {
            throw new \InvalidArgumentException("$what must be a non-empty list.");
        }
        foreach ($value as $item) {
            if (!is_string($item) || trim($item, " \n\r\t\v\0") === '') {
                throw new \InvalidArgumentException("$what must only hold non-empty strings.");
            }
        }
        if (count(array_unique($value)) !== count($value)) {
            throw new \InvalidArgumentException("$what holds a value twice.");
        }

        return $value;
    }
}
