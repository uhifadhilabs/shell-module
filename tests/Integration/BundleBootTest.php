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

namespace UhifadhiLabs\Canopy\Tests\Integration;

use Twig\Loader\FilesystemLoader;
use UhifadhiLabs\Canopy\UhifadhiLabsCanopyBundle;

/**
 * The smoke test: registering the canopy in a real kernel compiles a real
 * container and gives Twig a real namespace. Every page in the platform — the
 * host's own and every module's — rides on those two facts.
 */
final class BundleBootTest extends CanopyKernelTestCase
{
    public function testTheBundleBootsInAHostKernel(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey('UhifadhiLabsCanopyBundle', $kernel->getBundles());
        self::assertInstanceOf(
            UhifadhiLabsCanopyBundle::class,
            $kernel->getBundle('UhifadhiLabsCanopyBundle'),
        );
    }

    /**
     * Config lives under "canopy:", not the class-derived "uhifadhi_labs_canopy:"
     * — the alias is part of the host contract and every installation writes it.
     */
    public function testItsConfigurationIsKeyedByTheCanopyAlias(): void
    {
        $kernel = self::bootKernel();

        self::assertSame('canopy', $kernel->getBundle('UhifadhiLabsCanopyBundle')
            ->getContainerExtension()?->getAlias());
    }

    public function testItsDefaultsReachTheContainer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertSame('Uhifadhi', $container->getParameter('canopy.brand_name'));
        self::assertSame('dashboard_index', $container->getParameter('canopy.home_route'));
        self::assertSame('light', $container->getParameter('canopy.default_theme'));
        self::assertFalse($container->getParameter('canopy.dev_tools'));
    }

    /**
     * THE NAMESPACE IS THE ADDRESS OF THE CONTRACT. Every module page in the
     * platform will name it — `{% extends '@UhifadhiLabsCanopy/page.html.twig' %}`
     * — so it is registered and asserted before the first template exists.
     * Symfony derives it from the bundle's name and the repository-root
     * templates/ directory; both halves of that are load-bearing and both are
     * checked here rather than assumed.
     */
    public function testItsTemplateNamespaceResolves(): void
    {
        self::bootKernel();

        $loader = $this->twig()->getLoader();
        self::assertInstanceOf(FilesystemLoader::class, $loader);
        self::assertContains('UhifadhiLabsCanopy', $loader->getNamespaces());

        $paths = $loader->getPaths('UhifadhiLabsCanopy');
        self::assertNotSame([], $paths, 'The canopy must expose its templates/ directory to Twig.');
        foreach ($paths as $path) {
            self::assertDirectoryExists($path);
        }
    }

    /**
     * The stylesheet path is a published constant because it is written in more
     * than one place; this pins it to the AssetMapper convention the host and
     * every module bundle already rely on, so a rename of the bundle cannot
     * silently move it.
     */
    public function testItPublishesItsStylesheetPath(): void
    {
        self::assertSame('bundles/uhifadhilabscanopy/canopy.css', UhifadhiLabsCanopyBundle::STYLESHEET);
    }
}
