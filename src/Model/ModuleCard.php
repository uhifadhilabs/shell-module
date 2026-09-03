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
 * ONE TILE IN THE CATALOGUE PICTURE.
 *
 * The crown draws this; it does not read it. `slug` is carried so a host can
 * key its own markup off the card it handed over — the crown itself never
 * compares it to anything, and a sweep of src/ and templates/ fails the build
 * if any module name is ever typed here.
 *
 * `status` is a word the seam chose; how "live" LOOKS is the crown's, and it is
 * the same chip vocabulary every other chip on every other page speaks.
 * `url` is nullable for the reason the host learned by shipping tiles that
 * 404'd: a catalogue row whose bundle declares no entry route has no pages yet,
 * and its tile is informational rather than broken.
 */
final class ModuleCard
{
    /**
     * @param non-empty-string $slug   the catalogue identity, for the host's use
     * @param non-empty-string $title  the tile's name
     * @param string           $status the seam's word: "live", "template", anything else
     * @param string           $source where its data comes from, as a stamp
     * @param string|null      $url    the module's entry page, or null if it has none yet
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $status = '',
        public string $source = '',
        public ?string $url = null,
    ) {
    }
}
