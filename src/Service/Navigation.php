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

namespace Uhifadhi\Shell\Service;

use Uhifadhi\Shell\Contract\NavigationSourceInterface;
use Uhifadhi\Shell\Model\NavItem;
use Uhifadhi\Shell\Model\NavSection;

/**
 * THE SIDEBAR'S CONTENT, COLLECTED — and nothing else.
 *
 * This class asks every registered source what goes in the nav, orders the
 * answers, checks the one invariant a nav has, and hands the result to a
 * template. It decides nothing about who may see a row: the crown holds no
 * authorization service and asks nothing about the viewer, because a renderer
 * with opinions about the team model would be the second place in the platform
 * where a permission is interpreted. A row the viewer may not have never
 * arrives here.
 *
 * READ LIVE. The sources are iterated on every call and nothing between them
 * and the sidebar caches, which is what makes "switch a module off" take effect
 * the same day rather than after a deploy.
 */
final class Navigation
{
    /**
     * @param iterable<NavigationSourceInterface> $sources the tagged contributors, in registration order
     */
    public function __construct(private readonly iterable $sources)
    {
    }

    /**
     * Every section, in declared-position order with registration as the
     * tie-break.
     *
     * @return list<NavSection>
     */
    public function sections(): array
    {
        $sections = [];
        foreach ($this->sources as $source) {
            foreach ($source->sections() as $section) {
                $this->assertOneCurrent($section->items, \sprintf('the "%s" section', $section->label));
                $sections[] = $section;
            }
        }

        // Stable since PHP 8.0, which is what makes registration the tie-break
        // rather than an accident of the sort implementation.
        usort($sections, static fn (NavSection $a, NavSection $b): int => $a->position <=> $b->position);

        return $sections;
    }

    /**
     * EXACTLY ONE ROW IS CURRENT — among siblings, at every level of the tree.
     *
     * "Where am I" is the sidebar's whole job and two lit rows answer it worse
     * than none, so the crown refuses rather than rendering a nav that cannot
     * be read. The check is per sibling list rather than per sidebar, and the
     * distinction is the tree's: an area's row and the row of the screen you
     * are on inside it are both lit, on purpose — that is one PATH, drawn. Two
     * lit rows side by side is a contradiction; a lit row inside a lit branch
     * is a location.
     *
     * Zero is allowed and always will be: a viewer can be somewhere the nav
     * does not list, and a crown that refused to draw a sidebar on a sign-in
     * page would be a crown nobody could sign in to.
     *
     * @param list<NavItem> $items
     */
    private function assertOneCurrent(array $items, string $where): void
    {
        $lit = [];
        foreach ($items as $item) {
            if ($item->current) {
                $lit[] = $item->label;
            }
            $this->assertOneCurrent($item->children, \sprintf('the children of "%s"', $item->label));
        }

        if (\count($lit) > 1) {
            throw new \LogicException(\sprintf('The sidebar must light exactly one row among siblings, and %s lights %d of them (%s). Two lit rows answer "where am I" worse than none — the source decides which one it is.', $where, \count($lit), implode(', ', $lit)));
        }
    }
}
