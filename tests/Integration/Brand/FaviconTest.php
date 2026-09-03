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

namespace Uhifadhi\Shell\Tests\Integration\Brand;

use Uhifadhi\Shell\Tests\Integration\ContractTestCase;

/**
 * THE TAB ICON — the one piece of the brand a browser asks for by itself.
 *
 * A document that declares no icon gets a request for `/favicon.ico` from every
 * browser that loads it, and a fresh installation answers that
 * request with a 404 in its log on its first minute — the shell's own version
 * of the welcome-404: a correct installation looking like a broken one. So the
 * document declares the icon, and the bundle ships the file it points at.
 *
 * The mark is the FULL masterbrand — the knockout U tile with the child tile in
 * the cut corner — not a cropped glyph and not a redrawing. The geometry is the
 * ruled system and exists in exactly one form; this test compares the shipped
 * asset against `templates/_brand_mark.html.twig` path by path, because "the
 * favicon drifted from the mark" is a thing nobody notices for a year.
 */
final class FaviconTest extends ContractTestCase
{
    /** The stable part of the URL: AssetMapper appends a content digest to the file name. */
    private const string ASSET = 'bundles/uhifadhishell/favicon';

    /**
     * IN THE DOCUMENT, NOT IN A BLOCK. The bottom rung declares it, so a print
     * view, a full-bleed map screen and a framed module page all carry it
     * without any of them typing a link tag — and a module base that fills
     * `stylesheets` cannot lose it by forgetting `parent()`.
     */
    public function testEveryRungOfTheLadderDeclaresTheIcon(): void
    {
        foreach ([
            '@fixtures/bare_document_page.html.twig',
            '@fixtures/bare_shell_page.html.twig',
            '@fixtures/module_page.html.twig',
        ] as $page) {
            $icons = $this->crawl($page)->filter('head link[rel="icon"]');

            self::assertCount(1, $icons, \sprintf('%s must declare exactly one icon.', $page));
            self::assertSame('image/svg+xml', $icons->attr('type'), 'An SVG favicon: one file, every size, both palettes.');
            self::assertStringContainsString(self::ASSET, (string) $icons->attr('href'));
        }
    }

    /**
     * A link to an asset the bundle does not ship is the 404 moved, not fixed.
     * The bundle's public/ is exposed as `bundles/uhifadhishell/`, so the href
     * and the file on disk are the same statement in two places.
     */
    public function testTheBundleShipsTheFileItPointsAt(): void
    {
        self::assertFileExists(self::faviconPath());
        self::assertSame('favicon', basename(self::ASSET));
    }

    /**
     * THE GEOMETRY IS NEVER REDRAWN. Both paths, and the child's seat in the
     * cut corner, are copied from the mark partial rather than re-authored, so
     * a change to the masterbrand cannot leave the tab icon behind.
     */
    public function testItIsTheFullMasterbrandMarkAndNotARedrawing(): void
    {
        $favicon = self::favicon();
        $mark = file_get_contents(\dirname(__DIR__, 3).'/templates/_brand_mark.html.twig');
        self::assertIsString($mark);

        self::assertGreaterThan(0, preg_match_all('/ d="([^"]+)"/', $mark, $matches));
        foreach (array_unique($matches[1]) as $path) {
            self::assertStringContainsString($path, $favicon, 'The favicon carries the mark\'s own geometry, path for path.');
        }

        self::assertStringContainsString('translate(52,52) scale(0.48)', $favicon, 'The child tile is seated where the system seats it.');
    }

    /**
     * SELF-CONTAINED, AND THEREFORE BOTH PALETTES. A favicon is fetched on its
     * own: no shell.css, no `html.dark`, no tokens — so the paint is written
     * into the file, and the theme is the only place in the platform where the
     * brand colours are restated. They are restated as the mark partial rules
     * them: the accent green of each palette, with the child taking the canvas.
     */
    public function testItPaintsItselfInBothPalettesWithNothingFetched(): void
    {
        $favicon = self::favicon();

        self::assertStringContainsString('#0f8a68', $favicon, 'Light: deep jade on the bone paper.');
        self::assertStringContainsString('#f3f2eb', $favicon);
        self::assertStringContainsString('@media (prefers-color-scheme: dark)', $favicon);
        self::assertStringContainsString('#3ed9a8', $favicon, 'Dark: mint on the night canvas.');
        self::assertStringContainsString('#0c1310', $favicon);

        self::assertStringNotContainsString('url(', $favicon, 'Nothing is fetched: an icon that needs a second request is an icon that flickers.');
        self::assertStringNotContainsString('<image', $favicon);
        self::assertStringNotContainsString('var(--', $favicon, 'Custom properties do not reach a document the shell does not own.');
    }

    private static function favicon(): string
    {
        $svg = file_get_contents(self::faviconPath());
        self::assertIsString($svg);

        return $svg;
    }

    private static function faviconPath(): string
    {
        return \dirname(__DIR__, 3).'/public/favicon.svg';
    }
}
