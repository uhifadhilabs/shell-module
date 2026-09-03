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

namespace Uhifadhi\Shell\Tests\Integration\Welcome;

use Uhifadhi\Shell\Tests\Integration\ContractTestCase;

/**
 * THE FIRST PAGE A NEW INSTALLATION HAS.
 *
 * A fresh plant is the seam and the shell and nothing else, so it has no
 * routes, no controllers and — until now — no answer at `/` but Symfony's own
 * welcome-404. The shell cannot fix that with a route, because it owns no URLs
 * (Unit/BoundaryTest); it can ship the PAGE and let the application point a URL
 * at it, which is what the skeleton's config/routes/shell.yaml does with
 * Symfony's TemplateController.
 *
 * So this template is not chrome and it is not a stub: it is the one screen
 * that explains, to the person who has just planted an installation, what they
 * are looking at. It is written to be deleted the day they grow a real home
 * screen.
 *
 * It renders with NOTHING under it — no seam, no nav sources, no areas, no
 * user, no database — because that is exactly the installation it is for.
 */
final class WelcomePageTest extends ContractTestCase
{
    private const string WELCOME = '@UhifadhiShell/welcome.html.twig';

    /**
     * It is a page in the frame, not a loose fragment: sidebar, main column,
     * the one `.page` the frame owns. If the welcome screen typed its own
     * furniture it would be the first template in this bundle to do so.
     */
    public function testTheWelcomePageRendersInsideThePageFrame(): void
    {
        $crawler = $this->crawl(self::WELCOME);

        self::assertCount(1, $crawler->filter('aside.side'), 'The shell comes with the frame.');
        self::assertCount(1, $crawler->filter('main.main'));
        self::assertCount(1, $crawler->filter('main.main div.page'), 'The frame owns .page.');
        self::assertCount(1, $crawler->filter('div.pghead h1.pg'), 'A welcome page has a title like every other page.');
    }

    /**
     * IT NAMES THE TWO PACKAGES THAT ARE ACTUALLY INSTALLED, by their composer
     * names, because "the seam" and "the shell" are words that mean nothing to
     * somebody on their first day and `composer show` is where they will look
     * next. Naming a package is not naming a module: the shell still recognises
     * no module slug anywhere (Unit/BoundaryTest sweeps these same templates).
     */
    public function testItNamesWhatIsInstalledSoAFirstDayOperatorCanFindIt(): void
    {
        $html = $this->render(self::WELCOME);

        self::assertStringContainsString('uhifadhi/seam-module', $html);
        self::assertStringContainsString('uhifadhi/shell-module', $html);
    }

    /**
     * The empty sidebar beside it is not a bug and the page says so, in the
     * sentence that also says how to fill it. A person who reads this page and
     * then runs one `composer require` has understood the platform's shape.
     */
    public function testItExplainsTheEmptySidebarAndHowToFillIt(): void
    {
        $text = $this->crawl(self::WELCOME)->filter('div.pgbody')->text();

        self::assertStringContainsString('composer require', $text);
        self::assertStringContainsString('sidebar', $text);
    }

    /**
     * AND IT SAYS WHERE IT LIVES. The page is the application's to replace, so
     * it names the file that puts it at `/` rather than leaving somebody to
     * grep for it.
     */
    public function testItNamesTheFileThatPutsItAtTheRoot(): void
    {
        self::assertStringContainsString(
            'config/routes/shell.yaml',
            $this->crawl(self::WELCOME)->filter('div.pgbody')->text(),
        );
    }

    /**
     * It renders with no seams implemented at all — no nav sources, no tabs, no
     * place — because the installation it greets has none. This is the ring
     * gate, asserted on the one page that is guaranteed to be looked at first.
     */
    public function testItRendersOnAnInstallationThatHasNothingUnderItYet(): void
    {
        $crawler = $this->crawl(self::WELCOME);

        self::assertCount(0, $crawler->filter('nav.nav .nav-hd'), 'A fresh plant has no rows to show, and shows none.');
        self::assertCount(0, $crawler->filter('div.atabs'), 'It is inside no place, and says so by saying nothing.');
    }
}
