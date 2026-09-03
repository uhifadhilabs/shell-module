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

namespace Uhifadhi\Shell\Contract;

use Uhifadhi\Shell\Model\NavSection;
use Uhifadhi\Shell\UhifadhiShellBundle;

/**
 * WHERE THE SIDEBAR'S CONTENT COMES FROM.
 *
 * The shell owns the nav's SHAPE — sections, rows, the location tree, carets,
 * the current-row treatment, the collapsed rail — and none of its CONTENT.
 * Content arrives here, from whoever knows something worth putting there:
 *
 *   - the HOST implements one, and that is where domain data enters the shell.
 *     It has the areas, the viewer, the permission voters and the module seam's
 *     per-area ledger; folding those four into "these rows, in this order" is a
 *     reading for a person on a page, and it is the host's job.
 *   - a MODULE BUNDLE may implement one too, for the rare platform-wide row
 *     that belongs to nobody's area.
 *
 * GATING IS YOURS, NOT THE SHELL'S. The shell holds no authorization service
 * and asks nothing about the viewer. A row the viewer may not have is simply
 * not in what you return — there is no "hidden" flag, because a hidden row is a
 * row that leaks its existence to whoever reads the HTML.
 *
 * READ LIVE, NEVER CACHED. This is called on every render, so switching a
 * module off takes its row with it the same day rather than after a deploy.
 * Build the answer in the method; do not build it in the constructor.
 *
 * Tag the service {@see UhifadhiShellBundle::NAV_TAG} — by hand, in your own
 * extension, because a reusable bundle's services are not autoconfigured:
 *
 *     $services->set(App\Shell\HostNavigation::class)->tag('shell.nav_section');
 */
interface NavigationSourceInterface
{
    /**
     * @return iterable<NavSection>
     */
    public function sections(): iterable;
}
