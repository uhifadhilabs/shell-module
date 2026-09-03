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

namespace UhifadhiLabs\Canopy\Tests\Phase2;

use Symfony\Component\DomCrawler\Crawler;
use UhifadhiLabs\Canopy\Tests\Integration\CanopyKernelTestCase;
use UhifadhiLabs\Canopy\Tests\Phase2\Fixtures\HostKernel;

/**
 * Shared plumbing for the contract specification.
 *
 * Note what is NOT here: a database, a schema tool, a fixture loader. The whole
 * of this suite is "render this and look at it", which is the shape a layout
 * bundle's tests should have and the shape they only keep if the bundle never
 * acquires a domain.
 *
 * The host kernel this boots is a STAND-IN HOST: it implements the canopy's
 * seams with fixtures. That is not a shortcut — it is the specification's main
 * claim. If the crown can be driven to a full page by a host that has no areas,
 * no modules and no database, then the seams are real seams and not a polite
 * name for reaching into the application.
 */
abstract class Phase2TestCase extends CanopyKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return HostKernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        HostKernel::reset();
    }

    protected function tearDown(): void
    {
        HostKernel::reset();

        parent::tearDown();
    }

    /**
     * Every block a template declares, its own and every one it inherits —
     * which is the list a module author actually sees, and therefore the list
     * the contract is about.
     *
     * @return list<string>
     */
    protected function blocksOf(string $template): array
    {
        $blocks = $this->twig()->load($template)->getBlockNames();
        sort($blocks);

        return array_values($blocks);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function render(string $template, array $context = []): string
    {
        return $this->twig()->render($template, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function crawl(string $template, array $context = []): Crawler
    {
        return new Crawler($this->render($template, $context));
    }

    /**
     * The shipped stylesheet, read as text. Tokens are a contract like the
     * blocks are, and the only place they exist is this file.
     */
    protected function stylesheet(): string
    {
        $css = file_get_contents(__DIR__.'/../../public/canopy.css');
        self::assertIsString($css, 'The canopy ships one stylesheet, and the theme contract lives in it.');

        return $css;
    }

    protected function service(string $id): object
    {
        $service = self::getContainer()->get('test.'.$id);
        \assert(\is_object($service));

        return $service;
    }
}
