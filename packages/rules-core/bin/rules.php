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

/*
 * The rules registry of the Module-Factory (ps-module rules): validate | list | show <ID> | stage <stage> | generate.
 * See PrestaShopHeaven\Build\RulesCore\Cli for the options.
 */

declare(strict_types=1);

foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../../../autoload.php', __DIR__ . '/../../../vendor/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}

exit(PrestaShopHeaven\Build\RulesCore\Cli::run(
    array_slice($argv, 1),
    static fn (): PrestaShopHeaven\Build\RulesCore\Registry => PrestaShopHeaven\Build\RulesCore\Sources::registry([(string) getcwd(), __DIR__]),
    STDOUT,
    STDERR,
));
