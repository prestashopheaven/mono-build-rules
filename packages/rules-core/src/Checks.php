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
 * Who checks a rule: the PrestaShop validator, a tool of the definition of done, or a reviewer. A tool can be named
 * precisely as "tool:<step>" (a step of ps-module verify, for example tool:phpstan); plain "tool" means some step of
 * composer check that is not named yet.
 */
final class Checks
{
    /** The PrestaShop Addons validator (ps-module validate, validator-api). */
    public const VALIDATOR = 'validator';

    /** A tool of the definition of done, not named precisely yet. */
    public const TOOL = 'tool';

    /** Only a reviewer can judge the rule (the reviewer agent of the Module-Factory). */
    public const REVIEW = 'review';

    /** The steps of the definition of done a rule can name as tool:<step>. */
    public const TOOL_STEPS = [
        'assets', 'lint', 'cs', 'phpstan', 'compat', 'conventions', 'architecture', 'tests', 'coverage', 'translations',
        'validate', 'docs', 'build', 'validate-zip', 'shops', 'validator-api', 'context', 'git', 'publish', 'mono', 'gate',
    ];

    /**
     * Whether $check is a known way of checking a rule.
     */
    public static function isKnown(string $check): bool
    {
        if (in_array($check, [self::VALIDATOR, self::TOOL, self::REVIEW], true)) {
            return true;
        }

        return str_starts_with($check, self::TOOL . ':') && in_array(substr($check, strlen(self::TOOL) + 1), self::TOOL_STEPS, true);
    }

    /**
     * Throws when $check is unknown.
     *
     * @throws \InvalidArgumentException naming $context
     */
    public static function assertKnown(string $check, string $context): void
    {
        if (!self::isKnown($check)) {
            throw new \InvalidArgumentException(sprintf('%s: unknown check "%s" (known: validator, tool, tool:<%s>, review).', $context, $check, implode('|', self::TOOL_STEPS)));
        }
    }
}
