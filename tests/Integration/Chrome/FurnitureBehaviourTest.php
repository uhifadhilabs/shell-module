<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Shell Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uhifadhi\Shell\Tests\Integration\Chrome;

use PHPUnit\Framework\Attributes\DataProvider;
use Uhifadhi\Shell\Model\AreaTab;
use Uhifadhi\Shell\Service\Theme;
use Uhifadhi\Shell\Tests\Integration\ContractTestCase;
use Uhifadhi\Shell\Tests\Integration\Fixtures\HostKernel;
use Uhifadhi\Shell\UhifadhiShellBundle;

/**
 * SPEC 6 — THE FURNITURE MOVES.
 *
 * The shell shipped three controls whose behaviour lived somewhere else: the
 * theme toggle, the sidebar's collapse and the tree's carets all carried a
 * `data-action` naming a Stimulus controller the host was expected to write.
 * The host the shell was extracted from had written them; every other
 * installation had a sun button that did nothing, a collapse button that
 * collapsed nothing and carets that folded nothing. Furniture that looks
 * operable and is not is worse than furniture that is not there.
 *
 * The reasoning behind the split was that a bundle cannot contribute an
 * importmap entry. That is true and it was never the whole story: a UX package
 * ships Stimulus controllers through an AssetMapper path plus a
 * `symfony.controllers` block in assets/package.json, and Flex enables them in
 * the host's assets/controllers.json on install. So the behaviour ships here,
 * the same way symfony/ux-turbo ships its own, and the shell requires the two
 * packages that make it work rather than hoping the host installed them.
 *
 * This file pins the wiring end to end EXCEPT the click itself, which is a
 * browser's business: that the markup names a controller, that the controller
 * is in the package, that the package declares it under the name the host will
 * enable, and that the identifier in the template is the one StimulusBundle
 * derives from that package name.
 */
final class FurnitureBehaviourTest extends ContractTestCase
{
    /**
     * Every control the shell draws that presumes JavaScript, and the
     * controller#method that answers it. The list IS the sweep: a control added
     * to a shell template without a row here is a control nobody wired.
     *
     * @return \Generator<string, array{string, string, string}> page, selector, action
     */
    public static function furniture(): \Generator
    {
        yield 'the theme toggle' => [
            '@fixtures/bare_shell_page.html.twig',
            'header.topbar button.tb-icon',
            'theme#toggle',
        ];

        yield 'the sidebar collapse' => [
            '@fixtures/bare_shell_page.html.twig',
            'aside.side button.collapse-btn',
            'sidebar#toggle',
        ];

        yield 'a tree caret' => [
            '@fixtures/bare_shell_page.html.twig',
            'nav.nav button.chev',
            'sidebar-tree#fold',
        ];
    }

    #[DataProvider('furniture')]
    public function testEveryControlTheShellDrawsIsWiredToAControllerTheShellShips(
        string $page,
        string $selector,
        string $action,
    ): void {
        // The caret only exists when the nav has a branch, and the branch is
        // the host's data — so the stand-in host is given one.
        HostKernel::$areaTabs = [
            new AreaTab(label: 'Where you are', url: '/a', current: true),
            new AreaTab(label: 'And its sibling', url: '/b'),
        ];
        HostKernel::$mirrorAreaTabsIntoNav = true;

        $controls = $this->crawl($page)->filter($selector);
        self::assertGreaterThan(0, $controls->count(), 'The control the row is about is not on the page.');

        $actions = $controls->each(static fn ($node): ?string => $node->attr('data-action'));
        self::assertSame(
            array_fill(0, \count($actions), UhifadhiShellBundle::CONTROLLER_PREFIX.$action),
            $actions,
            'A control whose behaviour is somebody else\'s is a control that does nothing.',
        );
    }

    /**
     * THE IDENTIFIER IS NOT A NAME THE SHELL CHOSE. StimulusBundle derives it
     * from the composer package name Flex keys controllers.json by: the '@'
     * dropped, '/' and '_' becoming '-'. Typing anything else in a template
     * produces an attribute that binds to nothing, silently, which is exactly
     * the failure this whole file exists to end — so the prefix is computed
     * here the way StimulusBundle computes it and compared with the constant
     * the templates are written from.
     */
    public function testTheControllerPrefixIsTheOneStimulusDerivesFromThePackageName(): void
    {
        $composer = json_decode((string) file_get_contents(\dirname(__DIR__, 3).'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        self::assertIsString($composer['name'] ?? null);

        self::assertSame('@'.$composer['name'], UhifadhiShellBundle::ASSET_NAMESPACE);
        self::assertSame(
            str_replace(['_', '/'], ['-', '--'], $composer['name']).'--',
            UhifadhiShellBundle::CONTROLLER_PREFIX,
        );
    }

    /**
     * THE ONE KEYWORD THAT IS NOT DECORATION. Flex reads a package's
     * assets/package.json only if the composer package declares the keyword
     * `symfony-ux` (PackageJsonSynchronizer::resolvePackageJson), so without it
     * the controllers below are shipped, mapped, named in the templates — and
     * never written into the host's assets/controllers.json, which means never
     * loaded. Everything installs; nothing binds. This was found by installing the
     * package, not by reading it.
     */
    public function testThePackageIsMarkedAsAUxPackageOrFlexWillNotLookInside(): void
    {
        $composer = json_decode((string) file_get_contents(\dirname(__DIR__, 3).'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $keywords = $composer['keywords'] ?? null;
        self::assertIsArray($keywords);

        self::assertContains('symfony-ux', $keywords);
    }

    /**
     * THE PACKAGE DECLARES WHAT THE MARKUP CALLS. `assets/package.json` is the
     * UX contract: Flex reads it on install and writes each controller into the
     * host's assets/controllers.json, and StimulusBundle reads it back to find
     * the file. A controller named in a template and missing here is a 404 in
     * the console on every page.
     */
    public function testThePackageShipsEveryControllerTheTemplatesName(): void
    {
        $package = self::assetPackage();

        $symfony = $package['symfony'] ?? null;
        self::assertIsArray($symfony);
        $controllers = $symfony['controllers'] ?? null;
        self::assertIsArray($controllers);
        self::assertSame(['theme', 'sidebar', 'sidebar-tree'], array_keys($controllers));

        foreach ($controllers as $name => $config) {
            self::assertIsArray($config);
            self::assertTrue($config['enabled'] ?? false, \sprintf('The shell\'s own furniture is not opt-in: %s must ship enabled.', $name));
            self::assertSame('eager', $config['fetch'] ?? null, 'Chrome is on every page; a lazily fetched toggle is a toggle that misses the first click.');
            self::assertIsString($config['main'] ?? null);
            self::assertFileExists(\dirname(__DIR__, 3).'/assets/'.$config['main']);
        }
    }

    /**
     * The npm-side name has to be the composer name with an '@', because that
     * is the key Flex writes and the key StimulusBundle resolves back to this
     * directory. It is the single likeliest thing to get wrong and the hardest
     * to see: everything installs, and nothing binds.
     */
    public function testTheAssetPackageIsNamedTheWayFlexWillKeyIt(): void
    {
        self::assertSame(UhifadhiShellBundle::ASSET_NAMESPACE, self::assetPackage()['name'] ?? null);
    }

    /**
     * THE DOCUMENT EMITS THE IMPORTMAP. Without it no controller loads on any
     * page and the whole chain above is decoration. It was left to the host on
     * the same reasoning that left the behaviour to the host, and it failed the
     * same way; the shell requires asset-mapper, so `app` is an entrypoint
     * every installation has.
     */
    public function testTheDocumentRendersTheApplicationsImportmap(): void
    {
        $html = $this->render('@fixtures/bare_document_page.html.twig');

        self::assertStringContainsString('<script type="importmap"', $html);
        self::assertStringContainsString('"app"', $html);
    }

    /**
     * THE THEME'S PRE-PAINT SCRIPT KEEPS THE READING; the controller only
     * writes. A controller connects after the first frame, so a visitor who
     * chose dark would be shown a white page first — and the sidebar's
     * remembered width is the same kind of fact, resolved the same way, or a
     * 236px sidebar visibly jumps to 66px on every load.
     */
    public function testWhatIsRememberedIsAppliedBeforeTheFirstPaintNotOnConnect(): void
    {
        $html = $this->render('@fixtures/bare_shell_page.html.twig');

        $theme = strpos($html, Theme::CHOICE_KEY);
        $rail = strpos($html, 'shell-sidebar');
        $sheet = strpos($html, 'uhifadhishell/shell');

        self::assertIsInt($theme);
        self::assertIsInt($rail, 'The remembered rail must be resolved in the head, not on connect.');
        self::assertIsInt($sheet);
        self::assertLessThan($sheet, $theme);
        self::assertLessThan($sheet, $rail);

        self::assertStringContainsString('html.shell-rail .side', $this->stylesheet(), 'A pre-paint class nothing styles is a pre-paint class that does nothing.');
        self::assertStringContainsString('.side.rail', $this->stylesheet(), 'The sidebar\'s own class stays supported: the controller sets it on click.');
    }

    /**
     * The two sides of the remembered state agree on their key, and the theme's
     * is the published constant rather than a string typed twice.
     */
    public function testTheControllersWriteTheKeysTheHeadReads(): void
    {
        self::assertStringContainsString(Theme::CHOICE_KEY, self::controller('theme_controller.js'));
        self::assertStringContainsString("classList.toggle('dark')", self::controller('theme_controller.js'));

        $sidebar = self::controller('sidebar_controller.js');
        self::assertStringContainsString('shell-sidebar', $sidebar);
        self::assertStringContainsString('shell-rail', $sidebar);
        self::assertStringContainsString("'rail'", $sidebar);

        self::assertStringContainsString('closed', self::controller('sidebar_tree_controller.js'));
    }

    /**
     * @return array<string, mixed>
     */
    private static function assetPackage(): array
    {
        $json = json_decode((string) file_get_contents(\dirname(__DIR__, 3).'/assets/package.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($json);

        /** @var array<string, mixed> $json */
        return $json;
    }

    private static function controller(string $file): string
    {
        $js = file_get_contents(\dirname(__DIR__, 3).'/assets/controllers/'.$file);
        self::assertIsString($js);

        return $js;
    }
}
