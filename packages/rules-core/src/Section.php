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
 * One section of the conventions (one rules JSON file): its number and title, the rule prefixes it owns, and its
 * blocks in order: rules, and the Markdown text between them (intros, sub-headings, blank lines).
 */
final class Section
{
    /**
     * A checked section; use fromArray().
     *
     * @param list<string> $prefixes
     * @param list<Rule|string> $blocks
     */
    private function __construct(
        public readonly string $number,
        public readonly string $title,
        public readonly array $prefixes,
        public readonly array $blocks,
    ) {
    }

    /**
     * A section from the content of a rules JSON file.
     *
     * @param array<mixed> $data
     *
     * @throws \InvalidArgumentException when a field is missing or wrong, a block has an unknown type, or a rule's
     *                                   prefix does not belong to the section
     */
    public static function fromArray(array $data, string $source): self
    {
        $number = $data['number'] ?? null;
        $title = $data['title'] ?? null;
        $prefixes = $data['prefixes'] ?? null;
        $blocks = $data['blocks'] ?? null;
        if (!is_string($number) || !preg_match('/^\d+[a-z]?$/', $number)) {
            throw new \InvalidArgumentException("$source: number must be like \"5\" or \"5b\".");
        }
        if (!is_string($title) || trim($title, " \n\r\t\v\0") === '') {
            throw new \InvalidArgumentException("$source: title must be a non-empty string.");
        }
        if (!is_array($prefixes) || $prefixes === [] || !array_is_list($prefixes)) {
            throw new \InvalidArgumentException("$source: prefixes must be a non-empty list.");
        }
        foreach ($prefixes as $prefix) {
            if (!is_string($prefix) || !preg_match('/^[A-Z]{2,5}$/', $prefix)) {
                throw new \InvalidArgumentException("$source: a prefix must be 2 to 5 capitals.");
            }
        }
        if (!is_array($blocks) || !array_is_list($blocks)) {
            throw new \InvalidArgumentException("$source: blocks must be a list.");
        }
        $parsed = [];
        foreach ($blocks as $i => $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if ($type === 'text' && is_string($block['markdown'] ?? null)) {
                $parsed[] = $block['markdown'];
            } elseif ($type === 'rule') {
                $rule = Rule::fromArray($block);
                if (!in_array($rule->prefix(), $prefixes, true)) {
                    throw new \InvalidArgumentException("$source: rule {$rule->id} does not belong to this section (" . implode(', ', $prefixes) . ').');
                }
                $parsed[] = $rule;
            } else {
                throw new \InvalidArgumentException("$source: block $i must be {\"type\":\"rule\",…} or {\"type\":\"text\",\"markdown\":…}.");
            }
        }

        return new self($number, $title, $prefixes, $parsed);
    }

    /**
     * The rules of the section, in order.
     *
     * @return list<Rule>
     */
    public function rules(): array
    {
        return array_values(array_filter($this->blocks, static fn (Rule|string $block): bool => $block instanceof Rule));
    }

    /**
     * The Markdown heading of the section: "## 5b. Documentatie (DOC)".
     */
    public function heading(): string
    {
        return sprintf('## %s. %s (%s)', $this->number, $this->title, implode(', ', $this->prefixes));
    }

    /**
     * The section as Markdown: the heading, then every block as written.
     */
    public function markdown(): string
    {
        $lines = [$this->heading()];
        foreach ($this->blocks as $block) {
            $lines[] = $block instanceof Rule ? $block->markdown : $block;
        }

        return implode("\n", $lines);
    }

    /**
     * The content of the rules JSON file of the section (the inverse of fromArray()).
     *
     * @return array{number: string, title: string, prefixes: list<string>, blocks: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'title' => $this->title,
            'prefixes' => $this->prefixes,
            'blocks' => array_map(
                static fn (Rule|string $block): array => $block instanceof Rule ? $block->toArray() : ['type' => 'text', 'markdown' => $block],
                $this->blocks,
            ),
        ];
    }
}
