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
 * The stages of the Module-Factory pipeline, in order. A rule names the stages where it applies; "all" means every
 * stage (for example the rules for parallel sessions).
 */
final class Stages
{
    /** The pseudo-stage of a rule that applies everywhere. */
    public const ALL = 'all';

    /** The stages in pipeline order. */
    public const ORDER = ['intake', 'research', 'design', 'build', 'verify', 'content', 'release', 'retro'];

    /**
     * Whether $stage is a stage of the pipeline or "all".
     */
    public static function isKnown(string $stage): bool
    {
        return $stage === self::ALL || in_array($stage, self::ORDER, true);
    }

    /**
     * Throws when $stage is unknown.
     *
     * @throws \InvalidArgumentException naming $context and the known stages
     */
    public static function assertKnown(string $stage, string $context): void
    {
        if (!self::isKnown($stage)) {
            throw new \InvalidArgumentException(sprintf('%s: unknown stage "%s" (known: %s, %s).', $context, $stage, implode(', ', self::ORDER), self::ALL));
        }
    }
}
