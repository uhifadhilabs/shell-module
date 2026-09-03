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
 * A fresh installation is the seam and the shell and nothing else, so it has no
 * routes, no controllers and — until now — no answer at `/` but Symfony's own
 * welcome-404. The shell cannot fix that with a route, because it owns no URLs
 * (Unit/BoundaryTest); it can ship the PAGE and let the application point a URL
 * at it, which is what the skeleton's config/routes/shell.yaml does with
 * Symfony's TemplateController.
 *
 * So this template is not chrome and it is not a stub: it is the one screen
 * that explains, to the person who has just installed it, what they
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
     * IT LISTS WHAT IS ACTUALLY INSTALLED, and it asks Composer rather than
     * remembering. The page used to state that two packages were installed and
     * name them both in prose — true of the installation it was written for and wrong
     * the moment anybody ran a `composer require`, which is the one instruction
     * the page itself gives. A first-day operator would then be told, on the
     * platform's own welcome screen, that the module they had just installed
     * was not there.
     *
     * The list comes from Composer\InstalledVersions: every uhifadhi/* package
     * this installation has, with its version. Naming a package is still not
     * naming a module — nothing here is recognised, compared or switched on;
     * the shell prints whatever the vendor directory reports.
     */
    public function testItListsEveryInstalledPackageRatherThanTheTwoItWasWrittenFor(): void
    {
        $crawler = $this->crawl(self::WELCOME);
        $rows = $crawler->filter('div.pgbody .wpkgs .wpkg-row');

        $installed = array_values(array_filter(
            \Composer\InstalledVersions::getInstalledPackages(),
            static fn (string $package): bool => str_starts_with($package, 'uhifadhi/'),
        ));

        self::assertNotSame([], $installed, 'The suite runs inside an installation of this very package.');
        self::assertCount(\count($installed), $rows, 'The list is Composer\'s, not a list somebody typed.');

        $text = $rows->text();
        foreach ($installed as $package) {
            self::assertStringContainsString($package, $text);
        }
    }

    /**
     * A NAME WITHOUT A VERSION IS HALF AN ANSWER — "which shell am I running"
     * is the question this page is looked at to answer on the day something is
     * wrong, and `composer show` is the second place somebody looks, not the
     * first.
     */
    public function testEveryPackageIsShownWithItsVersion(): void
    {
        $rows = $this->crawl(self::WELCOME)->filter('div.pgbody .wpkgs .wpkg-row');

        foreach ($rows as $row) {
            $name = (new \Symfony\Component\DomCrawler\Crawler($row))->filter('.wpkg')->text();
            $version = (new \Symfony\Component\DomCrawler\Crawler($row))->filter('.chip')->text();

            self::assertStringStartsWith('uhifadhi/', trim($name));
            self::assertNotSame('', trim($version), \sprintf('%s is listed with no version.', $name));
        }
    }

    /**
     * THE TEACHING IS ON THE LIST, not beside it. "The seam" and "the shell"
     * are words that mean nothing on a first day, so the two packages the shell
     * can honestly speak for carry a line saying what they are — as annotations
     * on their own rows. There is no second inventory anywhere on the page: one
     * list, one source of truth, and the descriptions live on it.
     */
    public function testTheTwoPackagesTheShellCanSpeakForAreDescribedOnTheirOwnRows(): void
    {
        $crawler = $this->crawl(self::WELCOME);

        $described = $crawler->filter('div.pgbody .wpkgs .wpkg-row:has(.wpkg-note)');
        self::assertGreaterThan(0, $described->count());

        foreach ($described as $row) {
            $name = trim((new \Symfony\Component\DomCrawler\Crawler($row))->filter('.wpkg')->text());
            self::assertContains($name, ['uhifadhi/seam-module', 'uhifadhi/shell-module'], \sprintf(
                'The shell described %s. It can speak for itself and for the seam, and for nothing else.',
                $name,
            ));
        }

        self::assertStringContainsString(
            'uhifadhi/shell-module',
            $described->text(),
            'This suite runs inside an installation of the shell, so the shell describes itself here.',
        );
    }

    /**
     * A PACKAGE THE SHELL CANNOT SPEAK FOR GETS NO INVENTED SENTENCE, and the
     * page says the honest generic thing once instead: that a package without
     * sidebar rows is not a broken one.
     */
    public function testItSaysOnceThatAPackageWithoutRowsIsNotBroken(): void
    {
        $text = $this->crawl(self::WELCOME)->filter('div.pgbody')->text();

        self::assertStringContainsString('not a broken', $text);
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

        self::assertCount(0, $crawler->filter('nav.nav .nav-hd'), 'A fresh installation has no rows to show, and shows none.');
        self::assertCount(0, $crawler->filter('div.atabs'), 'It is inside no place, and says so by saying nothing.');
    }
}
