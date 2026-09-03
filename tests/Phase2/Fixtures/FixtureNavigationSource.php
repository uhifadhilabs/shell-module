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

namespace Uhifadhi\Canopy\Tests\Phase2\Fixtures;

use Uhifadhi\Canopy\Contract\NavigationSourceInterface;
use Uhifadhi\Canopy\Model\AreaTab;
use Uhifadhi\Canopy\Model\NavItem;
use Uhifadhi\Canopy\Model\NavSection;

/**
 * A host's nav, in the smallest honest form: something that answers "what goes
 * in the sidebar" and nothing else. A real one folds areas, the viewer's
 * permissions and the trunk's per-area ledger into the same answer; that it can
 * be replaced by four lines of fixture is the seam working.
 *
 * Read at call time, never at construction — the same-day promise.
 */
final class FixtureNavigationSource implements NavigationSourceInterface
{
    public function sections(): iterable
    {
        foreach (HostKernel::$navSources as $section) {
            yield $section;
        }

        if (!HostKernel::$mirrorAreaTabsIntoNav || [] === HostKernel::$areaTabs) {
            return;
        }

        // The sidebar's area branch, built from the SAME list the strip draws —
        // which is the point of the assertion that reads it back. Two hand-kept
        // copies is what the host has today.
        yield new NavSection('Observatory', [
            new NavItem(label: 'Areas', url: '/areas', icon: 'lucide:map', children: [
                new NavItem(
                    label: 'Test Area',
                    url: '/areas/x',
                    current: true,
                    open: true,
                    children: array_map(
                        static fn (AreaTab $tab): NavItem => new NavItem(
                            label: $tab->label,
                            url: $tab->url,
                            current: $tab->current,
                        ),
                        HostKernel::$areaTabs,
                    ),
                ),
            ]),
        ]);
    }
}
