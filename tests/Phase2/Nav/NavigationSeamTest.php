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

namespace Uhifadhi\Canopy\Tests\Phase2\Nav;

use Uhifadhi\Canopy\Contract\NavigationSourceInterface;
use Uhifadhi\Canopy\Model\NavItem;
use Uhifadhi\Canopy\Model\NavSection;
use Uhifadhi\Canopy\Service\Navigation;
use Uhifadhi\Canopy\Tests\Phase2\Fixtures\HostKernel;
use Uhifadhi\Canopy\Tests\Phase2\Phase2TestCase;
use Uhifadhi\Canopy\UhifadhiCanopyBundle;

/**
 * SPEC 2 — THE NAV SEAM.
 *
 * How a module's entry gets into the sidebar without the sidebar knowing that
 * modules exist.
 *
 * THE RULING. The crown owns the nav's SHAPE — sections, rows, a location tree,
 * carets, the current-row treatment, the collapsed rail. It owns none of the
 * nav's CONTENT. Content arrives through {@see NavigationSourceInterface},
 * implemented by whoever knows something worth putting there:
 *
 *   - the HOST implements one, and that is where trunk data enters the crown.
 *     The host has the areas, the viewer, the permission voters and the trunk's
 *     per-area ledger; folding those four into "these rows, in this order" is a
 *     reading for a person on a page, which is the host's job by the same
 *     argument the trunk used to hand the module grid away.
 *   - a MODULE BUNDLE may implement one too, tagged canopy.nav_section, for the
 *     rare platform-wide row that belongs to nobody's area.
 *
 * The crown never asks "which modules are installed", because it has no way to
 * ask that question that does not end in requiring the trunk. It asks "what
 * goes in the nav", and whatever answers, answers.
 *
 * THE ENFORCEMENT is negative and it is in Unit/BoundaryTest: no module slug
 * appears in src/ or templates/, ever, in any of the twelve real module names
 * the tree has. This file is the positive half — that a nav can be fully built
 * out of names the crown has never heard of.
 */
final class NavigationSeamTest extends Phase2TestCase
{
    private function navigation(): Navigation
    {
        $navigation = $this->service('canopy.navigation');
        \assert($navigation instanceof Navigation);

        return $navigation;
    }

    /**
     * THE WHOLE SEAM IN ONE TEST. Two sources, neither of them known to the
     * crown, both rendered — and the slugs are invented on purpose, because a
     * seam that only works for the modules that exist today is a hardcoded list
     * with extra steps.
     */
    public function testASourceContributesASectionAndTheCrownRendersIt(): void
    {
        HostKernel::$navSources = [
            'observatory' => new NavSection('Observatory', [
                new NavItem(label: 'Areas', url: '/areas', icon: 'lucide:map'),
            ]),
            'ferries' => new NavSection('Fleet', [
                new NavItem(label: 'Ferries', url: '/ferries', icon: 'lucide:ship'),
            ]),
        ];

        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertSame(
            ['Observatory', 'Fleet'],
            $crawler->filter('nav.nav div.nav-hd')->each(static fn ($n): string => trim($n->text())),
        );
        self::assertSame(
            ['Areas', 'Ferries'],
            $crawler->filter('nav.nav a.nav-item span')->each(static fn ($n): string => trim($n->text())),
        );
    }

    /**
     * SECTIONS COME OUT IN THE ORDER THEY WERE PUT IN. Registration order, and
     * a declared position as the tie-break — the trunk's ruling about
     * position() applies here for the same reason it applied there: a contract
     * field nothing reads is a lie in the contract.
     */
    public function testADeclaredPositionOrdersTheSectionsAndRegistrationBreaksTies(): void
    {
        HostKernel::$navSources = [
            'system' => new NavSection('System', [], position: 30),
            'org' => new NavSection('Organization', [], position: 20),
            'obs' => new NavSection('Observatory', [], position: 10),
        ];

        self::assertSame(
            ['Observatory', 'Organization', 'System'],
            array_map(static fn (NavSection $s): string => $s->label, $this->navigation()->sections()),
        );
    }

    /**
     * GATING IS THE SOURCE'S JOB, NOT THE CROWN'S. The crown holds no
     * AuthorizationChecker and calls is_granted on nothing — a renderer that
     * decides who may see a row is a renderer that has opinions about the team
     * model, and it would be the second place in the platform where a
     * permission is interpreted.
     *
     * A row the viewer may not have is absent from what the source returns.
     * There is no "hidden" flag, because a hidden row is a row that leaks its
     * existence to whoever reads the HTML.
     */
    public function testTheCrownDecidesNothingAboutWhoMaySeeARow(): void
    {
        HostKernel::$navSources = [
            'org' => new NavSection('Organization', [
                new NavItem(label: 'Departments', url: '/departments', icon: 'lucide:building'),
            ]),
        ];

        $html = $this->render('@fixtures/body_only_page.html.twig');

        self::assertStringContainsString('Departments', $html);
        self::assertStringNotContainsString('Team', $html, 'A row the source withheld must be absent, not hidden.');

        // And the crown must not have the means to ask.
        $source = file_get_contents(__DIR__.'/../../../src/Service/Navigation.php');
        self::assertIsString($source);
        self::assertStringNotContainsString('AuthorizationChecker', $source);
        self::assertStringNotContainsString('isGranted', $source);
    }

    /**
     * A ROW THAT EXISTS BUT HAS NOWHERE TO GO renders inert rather than
     * vanishing. The host already needs this — a surface whose route has not
     * merged yet — and the honest treatment is the one the design settled: the
     * row is visible, dimmed, and not a link, so the product tells you the
     * thing is coming instead of pretending it was never planned.
     */
    public function testARowWithNoDestinationRendersInertRatherThanDisappearing(): void
    {
        HostKernel::$navSources = [
            'system' => new NavSection('System', [
                new NavItem(label: 'Alerts', url: null, icon: 'lucide:bell', hint: 'Alerts — planned'),
            ]),
        ];

        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(0, $crawler->filter('nav.nav a.nav-item'));
        $inert = $crawler->filter('nav.nav span.nav-item.off');
        self::assertCount(1, $inert);
        self::assertSame('Alerts — planned', $inert->attr('title'));
    }

    /**
     * THE LOCATION TREE. A row may carry children, and a child may carry
     * children — which is what the settled sidebar does with areas: area → its
     * tabs → the modules under Modules. The crown renders the nesting; the
     * source decides the nesting, because "which tabs does an area have" is not
     * something a layout can know (spec 3).
     */
    public function testARowMayCarryATreeAndTheCrownRendersTheNestingItIsGiven(): void
    {
        HostKernel::$navSources = [
            'obs' => new NavSection('Observatory', [
                new NavItem(label: 'Areas', url: '/areas', icon: 'lucide:map', children: [
                    new NavItem(label: 'Test Area', url: '/areas/x', current: true, children: [
                        new NavItem(label: 'Sightings', url: '/areas/x/modules/sightings'),
                    ]),
                ]),
            ]),
        ];

        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(1, $crawler->filter('nav.nav .ntree'));
        self::assertSame('Test Area', trim($crawler->filter('nav.nav .ntree .nta b')->text()));
        self::assertSame('Sightings', trim($crawler->filter('nav.nav .ntree .ntm')->text()));
    }

    /**
     * FOLDING IS A CLASS, NEVER AN OMISSION. The host's sidebar learned this the
     * hard way: a caret that folds by not rendering its children has nothing to
     * reopen, and the row becomes a one-way door. The contract states it, so a
     * later performance-minded refactor cannot quietly reintroduce the bug.
     */
    public function testAFoldedBranchIsStillInTheDocument(): void
    {
        HostKernel::$navSources = [
            'obs' => new NavSection('Observatory', [
                new NavItem(label: 'Areas', url: '/areas', children: [
                    new NavItem(label: 'Test Area', url: '/areas/x', open: false, children: [
                        new NavItem(label: 'Sightings', url: '/areas/x/modules/sightings'),
                    ]),
                ]),
            ]),
        ];

        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(1, $crawler->filter('.ntm'), 'A folded child is present and classed, never dropped.');
        self::assertStringContainsString('closed', (string) $crawler->filter('.nta-group')->attr('class'));
    }

    /**
     * EXACTLY ONE ROW IS CURRENT. "Where am I" is the sidebar's whole job, and
     * two lit rows answer it worse than none. A parent never steals the state
     * from the child it is showing — the host's rule, kept.
     */
    public function testTwoCurrentRowsAreARefusalRatherThanARender(): void
    {
        HostKernel::$navSources = [
            'obs' => new NavSection('Observatory', [
                new NavItem(label: 'Areas', url: '/areas', current: true),
                new NavItem(label: 'Fleet', url: '/fleet', current: true),
            ]),
        ];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/exactly one/i');
        $this->navigation()->sections();
    }

    /**
     * UNINSTALLING TAKES THE ROW WITH IT, THIS REQUEST. The trunk's
     * attention-list promise, at the crown's end: sources are read live, per
     * render, from the container's tagged iterator — nothing between the source
     * and the sidebar is allowed to cache, or "switch the module off" becomes
     * "switch it off after a deploy".
     */
    public function testTheNavIsReadLiveSoARowVanishesTheSameDayItIsSwitchedOff(): void
    {
        HostKernel::$navSources = [
            'obs' => new NavSection('Observatory', [new NavItem(label: 'Sightings', url: '/s')]),
        ];
        self::assertStringContainsString('Sightings', $this->render('@fixtures/body_only_page.html.twig'));

        HostKernel::$navSources = [
            'obs' => new NavSection('Observatory', []),
        ];
        self::assertStringNotContainsString('Sightings', $this->render('@fixtures/body_only_page.html.twig'));
    }

    /**
     * The tag is a published constant on the bundle, for the reason the trunk's
     * is: a contributor writes the string by hand (a reusable bundle's services
     * are not autoconfigured) and should not be retyping it.
     */
    public function testTheSeamsTagIsPublished(): void
    {
        self::assertSame('canopy.nav_section', UhifadhiCanopyBundle::NAV_TAG);
        self::assertTrue(interface_exists(NavigationSourceInterface::class));
    }

    /**
     * ZERO SOURCES IS A WORKING INSTALLATION — the trunk's rule, inherited. A
     * crown with nothing to navigate renders a sidebar with a brand and no
     * rows, not an error and not a hole where the aside should be.
     */
    public function testACrownWithNothingToNavigateStillRenders(): void
    {
        HostKernel::$navSources = [];

        $crawler = $this->crawl('@fixtures/body_only_page.html.twig');

        self::assertCount(1, $crawler->filter('aside.side'));
        self::assertCount(1, $crawler->filter('a.brand'));
        self::assertCount(0, $crawler->filter('.nav-item'));
    }
}
