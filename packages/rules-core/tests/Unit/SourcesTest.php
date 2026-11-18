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
use PrestaShopHeaven\Build\RulesCore\Sources;

/**
 * The rules are found in a monorepo, a module's vendor/ or the workspace, from the folder the command runs in or above.
 */
final class SourcesTest extends TestCase
{
    public function testTheMonorepoLayoutIsFoundFromASubfolder(): void
    {
        $root = Fixtures::tempDir();
        Fixtures::writeJson("$root/packages/rules-b/rules/b.json", []);
        Fixtures::writeJson("$root/packages/rules-a/rules/a.json", []);
        mkdir("$root/packages/rules-a/src");

        self::assertSame(["$root/packages/rules-a/rules/a.json", "$root/packages/rules-b/rules/b.json"], Sources::files(["$root/packages/rules-a/src"]));
    }

    public function testAModuleUsesItsVendorRules(): void
    {
        $module = Fixtures::tempDir();
        Fixtures::writeJson("$module/vendor/prestashopheaven/build-rules-code/rules/str.json", []);

        self::assertSame(["$module/vendor/prestashopheaven/build-rules-code/rules/str.json"], Sources::files([$module]));
    }

    public function testTheWorkspaceUsesTheLibrariesCheckout(): void
    {
        $workspace = Fixtures::tempDir();
        Fixtures::writeJson("$workspace/libraries/mono-build-rules/packages/rules-code/rules/str.json", []);

        self::assertSame(["$workspace/libraries/mono-build-rules/packages/rules-code/rules/str.json"], Sources::files([$workspace]));
    }

    public function testNoRulesAnywhereIsAnError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No rules packages found');

        Sources::files([Fixtures::tempDir() . '/a/b/c/d/e/f/g/h/i']);
    }

    public function testThePreambleOfThisPackageIsShipped(): void
    {
        self::assertStringStartsWith('# ', Sources::preamble());
    }

    public function testAMissingPreambleIsAnError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is missing');

        Sources::preamble(Fixtures::tempDir() . '/none.md');
    }

    public function testTheRegistryIsBuiltFromTheFoundFiles(): void
    {
        $root = Fixtures::tempDir();
        Fixtures::writeJson("$root/packages/rules-code/rules/str.json", Fixtures::section());
        file_put_contents("$root/pre.md", "# Pre\n");

        $registry = Sources::registry([$root], "$root/pre.md");

        self::assertSame("# Pre\n", $registry->preamble);
        self::assertSame('STR-01', $registry->rules()[0]->id);
    }
}
