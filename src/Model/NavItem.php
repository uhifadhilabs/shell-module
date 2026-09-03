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

namespace Uhifadhi\Shell\Model;

/**
 * A ROW IN THE SIDEBAR, and possibly a branch of it.
 *
 * A value object on purpose, and the reason the crown can draw the seam's
 * answers without depending on the seam: there is nothing on this class to
 * branch on that is not also on every other row. The moment a domain entity is
 * in scope inside a Twig file, somebody writes `{% if row.entity.slug == … %}`
 * and the module-blindness the tree promises is gone.
 *
 * `url` is nullable and that is a product decision, not laziness: a surface
 * whose route has not merged yet renders visible, dimmed and inert, so the
 * product says "this is planned" instead of pretending it was never planned.
 * Compare {@see AreaTab}, where the opposite rule holds and for a stated reason.
 */
final class NavItem
{
    /**
     * @param non-empty-string $label    what the row says
     * @param string|null      $url      where it goes, or null for an inert row
     * @param string|null      $icon     a ux-icons name, e.g. "lucide:map"
     * @param string|null      $hint     the title attribute — why a row is inert
     * @param bool             $current  whether this is the row the viewer is on
     * @param bool             $open     whether this row's children are unfolded
     * @param list<self>       $children the branch under this row, if any
     */
    public function __construct(
        public string $label,
        public ?string $url = null,
        public ?string $icon = null,
        public ?string $hint = null,
        public bool $current = false,
        public bool $open = true,
        public array $children = [],
    ) {
    }
}
