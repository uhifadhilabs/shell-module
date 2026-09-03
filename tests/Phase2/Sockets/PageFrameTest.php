<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Canopy Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UhifadhiLabs\Canopy\Tests\Phase2\Sockets;

use UhifadhiLabs\Canopy\Contract\LayoutContract;
use UhifadhiLabs\Canopy\Tests\Phase2\Phase2TestCase;

/**
 * SPEC 1b — WHAT THE FRAME DOES WITH WHAT IT IS GIVEN.
 *
 * A block list is only half a contract: a socket that exists but renders the
 * markup in a different place, or renders an empty element for an empty block,
 * is a socket that a designer will work around and then a module will copy the
 * workaround. So the frame's OUTPUT is pinned too — the shape, not the pixels.
 * Pixels are the design workspace's authority; structure is this file's.
 *
 * The fixture page (tests/Phase2/Fixtures/templates/module_page.html.twig) is
 * written the way a module bundle's page should be written after the
 * extraction: extend the frame, fill the sockets, type no furniture.
 */
final class PageFrameTest extends Phase2TestCase
{
    private const string PAGE = '@fixtures/module_page.html.twig';

    /**
     * THE ONE MANDATORY SOCKET. A page that fills nothing but `canopy_page`
     * must still be a well-formed platform page: shell around it, `.page`
     * wrapper under it, footer below it. This is the sentence the whole bundle
     * is a mechanism for — "a module writes its body and gets the platform".
     */
    public function testAPageThatFillsOnlyItsBodyIsStillAWholePage(): void
    {
        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(1, $crawler->filter('aside.side'), 'The shell comes with the frame.');
        self::assertCount(1, $crawler->filter('main.main'));
        self::assertCount(1, $crawler->filter('main.main div.page'), 'The frame owns .page. A module must never type it.');
        self::assertSame('the body', trim($crawler->filter('main.main div.page')->text()));
    }

    public function testTheFrameRendersEverySocketItIsGivenInTheDesignedOrder(): void
    {
        $crawler = $this->crawl(self::PAGE);

        $order = $crawler->filter('div.page > *')->each(
            static fn ($node): string => (string) $node->attr('class'),
        );

        self::assertSame(['crumb', 'pghead', 'atabs', 'flashes', 'pgbody'], $order, <<<'WHY'
            The page frame's vertical order is part of the contract: trail,
            page head, tabs, flashes, body. It is not a stylesheet's opinion —
            a module filling canopy_page_actions has to know its buttons land
            beside the title and above the tabs, or it will place them itself.
            WHY);
    }

    public function testTheTitleTrailAndActionsLandWhereTheDesignPutsThem(): void
    {
        $crawler = $this->crawl(self::PAGE);

        self::assertSame('a fixture page', trim($crawler->filter('div.pghead h1.pg')->text()));
        self::assertSame('what it is for', trim($crawler->filter('div.pghead p.pgsub')->text()));
        self::assertSame('Do the thing', trim($crawler->filter('div.pghead div.pgact a.cta')->text()));
        self::assertStringContainsString('fixtures', $crawler->filter('div.crumb')->text());
    }

    /**
     * AN EMPTY SOCKET RENDERS NOTHING — not an empty element. This is the
     * difference between a frame you can trust and a frame that makes every
     * page carry a 24px gap it did not ask for, and it is the reason a module
     * author reaches for a wrapper div of their own.
     */
    public function testAnUnfilledSocketLeavesNoEmptyElementBehind(): void
    {
        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(0, $crawler->filter('p.pgsub'));
        self::assertCount(0, $crawler->filter('div.pgact'));
        self::assertCount(0, $crawler->filter('div.crumb'));
        self::assertCount(0, $crawler->filter('div.atabs'));
        self::assertCount(0, $crawler->filter('div.flashes'));
    }

    /**
     * FLASHES ARE THE FRAME'S, ONCE. Today the host renders them and then patrol
     * renders them again its own way and incidents renders them a third way,
     * each with its own class and its own tick glyph — so "saved" reads
     * differently depending on which screen you came from. One implementation,
     * in the frame, above the body.
     */
    public function testFlashesAreRenderedByTheFrameSoEveryModuleSaysSavedTheSameWay(): void
    {
        $crawler = $this->crawl(self::PAGE, [] /* the fixture host seeds a flash */);

        $flashes = $crawler->filter('div.flashes > div.c');
        self::assertCount(1, $flashes);
        self::assertStringContainsString('Saved.', $flashes->text());
        self::assertSame('success', $flashes->attr('data-canopy-flash'));
    }

    /**
     * THE STYLESHEET SOCKET COMPOSES. A module base adds its own sheet and must
     * get the crown's first — its rules are written to override the crown's,
     * and a base that forgets parent() ships a page with no theme at all. The
     * frame therefore links its own sheet in `stylesheets`, so `parent()` is
     * the thing that carries it and forgetting it fails visibly rather than
     * subtly.
     */
    public function testAModuleStylesheetLandsAfterTheCrownsOwn(): void
    {
        $html = $this->render(self::PAGE);

        $crown = strpos($html, 'uhifadhilabscanopy/canopy.css');
        $module = strpos($html, 'fixture-module.css');

        self::assertIsInt($crown);
        self::assertIsInt($module);
        self::assertLessThan($module, $crown, 'The crown\'s stylesheet must come first; a module overrides it, never the reverse.');
    }

    /**
     * The title socket is a plain Twig block, but what a page SHOULD put in it
     * is a platform decision, so the crown ships the joiner and every page uses
     * it: "<page> — <area> — <brand>", brand from config, em dashes, once.
     */
    public function testThePageTitleIsComposedByTheCrownRatherThanByEveryPage(): void
    {
        self::assertSame(
            'a fixture page — Test Area — Uhifadhi',
            trim($this->crawl(self::PAGE)->filter('title')->text()),
        );
    }

    /**
     * A module may extend any rung of the ladder. The middle rung — the shell
     * without the page frame — is what a full-bleed map screen needs, and if it
     * is not supported the module will extend the frame and then fight `.page`
     * with negative margins.
     */
    public function testTheShellIsUsableWithoutThePageFrame(): void
    {
        $crawler = $this->crawl('@fixtures/bare_shell_page.html.twig');

        self::assertCount(1, $crawler->filter('aside.side'));
        self::assertCount(0, $crawler->filter('div.page'));
        self::assertCount(1, $crawler->filter('#bare-slot'));
    }

    /**
     * And the bottom rung: the document with no furniture at all, for a print
     * view or an export. It still carries the theme, because a printed page
     * with no tokens is a white page with black Times New Roman.
     */
    public function testTheDocumentIsUsableWithoutTheShell(): void
    {
        $crawler = $this->crawl('@fixtures/bare_document_page.html.twig');

        self::assertCount(0, $crawler->filter('aside.side'));
        self::assertCount(1, $crawler->filter('html'));
        self::assertStringContainsString('canopy.css', $this->render('@fixtures/bare_document_page.html.twig'));
    }

    public function testTheFrameIsTheOneAddressAModuleNeedsToKnow(): void
    {
        // A module bundle's base extends exactly this string, and nothing else
        // about the crown's internals is public.
        self::assertSame('@UhifadhiLabsCanopy/page.html.twig', LayoutContract::PAGE);
    }
}
