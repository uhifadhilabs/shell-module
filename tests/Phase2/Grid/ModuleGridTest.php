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

namespace Uhifadhi\Shell\Tests\Phase2\Grid;

use Uhifadhi\Shell\Contract\LayoutContract;
use Uhifadhi\Shell\Model\ModuleCard;
use Uhifadhi\Shell\Model\ModuleGroup;
use Uhifadhi\Shell\Tests\Phase2\Phase2TestCase;

/**
 * SPEC 5 — THE MODULE GRID.
 *
 * THE OWNERSHIP RULING. The trunk explicitly declined the grid and named the
 * shell as its claimant, so this bundle had to answer rather than inherit. The
 * answer is a split, and the independent-life test is what splits it:
 *
 *   THE PICTURE IS THE CROWN'S. Cards in category groups, a status chip, a lens
 *   marker on the group a department leads, an empty state — that is a layout,
 *   it lives alone perfectly well given a list of groups, and it is the same
 *   layout wherever it appears (the area's Modules tab, a department's page, a
 *   future search result). One implementation.
 *
 *   THE GROUPING IS NOT. Which cards, in which groups, in which order, and
 *   which department is said to lead one — that is a reading of the catalogue
 *   for a particular viewer on a particular area, and it needs three things the
 *   crown does not have and must not acquire: the area, the viewer's identity,
 *   and the department lens. The host's ModuleGridService already does exactly
 *   this and stays where it is; the trunk's README argued the same boundary
 *   from the other side.
 *
 *   THE URL IS NOT, EITHER. /areas/{uuid}/modules is the host's URL space,
 *   gated by the host's module.view and resolving the host's area entity. A
 *   controller here would drag all three across the boundary to save an
 *   include. Unit/BoundaryTest fails the build if one appears.
 *
 *   AND THE CUSTOMIZE SCREEN IS NOT THE CROWN'S AT ALL. It looked like a
 *   neighbour of the grid and it is not: it is a form that WRITES per-area
 *   install state — a POST, a CSRF token, an authorization decision and a
 *   flush. The crown has no writes anywhere in it, and the first one would be
 *   the end of "the layout can be rendered from a fixture with no database".
 *   The host keeps the customize screen; the crown will draw its cards for it.
 *
 * SO THE CROWN DEPENDS ON NO TRUNK. It draws the trunk's answers and never
 * reads them: they arrive as ModuleGroup/ModuleCard, composed by whoever had
 * the area and the viewer. This is where the twig-bundle → framework-bundle
 * analogy breaks down and is worth saying why, since it is the obvious defence
 * of the opposite ruling: twig-bundle depends on framework-bundle for
 * MACHINERY — the kernel, the config pass — not to read its domain data. The
 * crown would be requiring the trunk to read data, and the moment a trunk
 * entity is in scope inside a template, somebody writes
 * `{% if module.slug == 'overview' %}` and the module-blindness both rings
 * promise is gone. A value object cannot be interrogated that way.
 */
final class ModuleGridTest extends Phase2TestCase
{
    /**
     * @return list<ModuleGroup>
     */
    private function fixtureGroups(): array
    {
        return [
            new ModuleGroup(label: 'Operations', department: 'Protection', cards: [
                new ModuleCard(slug: 'sightings', title: 'Sightings', status: 'live', source: 'field', url: '/areas/x/modules/sightings'),
                new ModuleCard(slug: 'ferries', title: 'Ferries', status: 'template', source: 'manifest', url: '/areas/x/modules/ferries'),
            ]),
            new ModuleGroup(label: 'Ecology', cards: [
                new ModuleCard(slug: 'shell-cover', title: 'Shell cover', status: 'planned', source: 'satellite'),
            ]),
        ];
    }

    public function testTheGridDrawsTheGroupsItIsHandedAndInventsNoOthers(): void
    {
        $crawler = $this->crawl(LayoutContract::MODULE_GRID, ['groups' => $this->fixtureGroups()]);

        self::assertSame(
            ['Operations', 'Ecology'],
            $crawler->filter('h2.zone')->each(static fn ($n): string => trim(explode('led by', $n->text())[0])),
        );
        self::assertSame(
            ['Sightings', 'Ferries', 'Shell cover'],
            $crawler->filter('.mtile .mtile-title')->each(static fn ($n): string => trim($n->text())),
        );
    }

    /**
     * THE LENS MARKER APPEARS ONLY WHEN A DEPARTMENT LEADS. A department is a
     * lens, never a gate — it says who leads a group of work, and a marker on
     * every group would say nothing at all.
     */
    public function testTheLensMarkerIsThereOnlyWhenSomebodyLeads(): void
    {
        $crawler = $this->crawl(LayoutContract::MODULE_GRID, ['groups' => $this->fixtureGroups()]);

        $markers = $crawler->filter('h2.zone .lens-led');
        self::assertCount(1, $markers);
        self::assertStringContainsString('Protection', $markers->text());
    }

    /**
     * A CARD WITH NOWHERE TO GO IS NOT A LINK. A catalogue row whose bundle
     * declares no entry route has no pages yet; its tile is informational. The
     * host learned this by shipping tiles that 404'd, and the rule is kept —
     * expressed here as a nullable url on the value object, which means a
     * template cannot forget to check.
     */
    public function testACardWithNoDestinationRendersInertRatherThanBroken(): void
    {
        $crawler = $this->crawl(LayoutContract::MODULE_GRID, ['groups' => $this->fixtureGroups()]);

        self::assertCount(2, $crawler->filter('a.mtile'));
        self::assertCount(1, $crawler->filter('div.mtile.mtile-inert'));
        self::assertSame('Shell cover', trim($crawler->filter('div.mtile-inert .mtile-title')->text()));
    }

    /**
     * The status chip's vocabulary is the crown's, because it is a visual
     * vocabulary shared with every chip on every other page — live/ok,
     * template/warn, anything else/idle. What a module's status IS remains the
     * trunk's; how "live" looks is the crown's.
     */
    public function testTheStatusChipSpeaksThePlatformsOneChipVocabulary(): void
    {
        $crawler = $this->crawl(LayoutContract::MODULE_GRID, ['groups' => $this->fixtureGroups()]);

        self::assertSame(
            ['chip ok', 'chip warn', 'chip idle'],
            $crawler->filter('.mtile .chip')->each(static fn ($n): string => (string) $n->attr('class')),
        );
    }

    /**
     * ZERO MODULES IS A WORKING AREA — the trunk's rule, at the surface where a
     * person meets it. An area with nothing installed gets a sentence, not a
     * blank plate and not an error.
     */
    public function testAnAreaWithNoModulesGetsASentenceRatherThanAHole(): void
    {
        $crawler = $this->crawl(LayoutContract::MODULE_GRID, ['groups' => []]);

        self::assertCount(0, $crawler->filter('.mtile'));
        self::assertCount(1, $crawler->filter('.c'));
        self::assertStringContainsString('No modules', $crawler->text());
    }

    /**
     * The grid's address is a constant, like the frames', because the host
     * includes it from at least two pages and a department page will make it
     * three.
     */
    public function testTheGridIsAddressableByConstantAndIsAPartialNotAPage(): void
    {
        self::assertSame('@UhifadhiShell/_module_grid.html.twig', LayoutContract::MODULE_GRID);
        self::assertContains(LayoutContract::MODULE_GRID, LayoutContract::PARTIALS);

        // A partial: it extends nothing and declares no socket. A grid that was
        // a page would own a title, a crumb and an opinion about the area.
        self::assertSame([], $this->blocksOf(LayoutContract::MODULE_GRID));
    }

    /**
     * THE CROWN WRITES NOTHING. The customize screen is the temptation this
     * guards — it is the grid's neighbour in the UI and its opposite in kind.
     */
    public function testTheCrownContainsNoFormThatWrites(): void
    {
        $templates = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__.'/../../../templates', \FilesystemIterator::SKIP_DOTS),
        );
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ('twig' === $file->getExtension()) {
                $templates[] = $file->getPathname();
            }
        }

        // A sweep over nothing passes, and a test that passes in the red suite
        // asserts nothing.
        self::assertNotSame([], $templates, 'There are no templates to sweep yet.');

        foreach ($templates as $template) {
            $code = (string) file_get_contents($template);
            self::assertStringNotContainsString('method="post"', strtolower($code), basename($template).' writes. The crown draws.');
            self::assertStringNotContainsString('csrf_token', $code, basename($template).' writes. The crown draws.');
        }
    }
}
