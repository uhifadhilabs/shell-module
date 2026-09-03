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

namespace UhifadhiLabs\Canopy\Tests\Phase2\Fixtures;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use UhifadhiLabs\Canopy\Model\AreaTab;
use UhifadhiLabs\Canopy\Model\NavSection;
use UhifadhiLabs\Canopy\Tests\Integration\TestKernel;
use UhifadhiLabs\Canopy\UhifadhiLabsCanopyBundle;

/**
 * A STAND-IN HOST — and the specification's main claim, not a testing
 * convenience.
 *
 * This kernel is an application that implements the crown's seams and has
 * nothing else at all: no areas, no modules, no trunk, no database, no user. If
 * the crown can be driven to a complete page by THIS, then the seams are real
 * seams rather than a polite name for reaching into the application, and the
 * "standalone" in the canopy's charter is a fact about the code.
 *
 * Everything a test wants to vary is a static, set in the test body and reset
 * between tests. The fixture services read them at render time, which is also
 * how a real source behaves — read live, never cached (see the nav seam's
 * same-day promise).
 */
final class HostKernel extends TestKernel
{
    /** @var array<string, NavSection> */
    public static array $navSources = [];

    /** @var list<AreaTab> */
    public static array $areaTabs = [];

    /**
     * Mirror the area's tabs into the sidebar's location tree as well, so a
     * test can assert the strip and the branch cannot disagree — the host keeps
     * two hand-written copies today.
     */
    public static bool $mirrorAreaTabsIntoNav = false;

    /** @var list<array{string, string}> label => message, seeded before a render */
    public static array $flashes = [['success', 'Saved.']];

    public static function reset(): void
    {
        self::$navSources = [];
        self::$areaTabs = [];
        self::$mirrorAreaTabsIntoNav = false;
        self::$flashes = [['success', 'Saved.']];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);

        // The fixture pages live under their own namespace so that no test can
        // accidentally assert against a template the crown ships.
        $container->extension('twig', [
            'paths' => [__DIR__.'/templates' => 'fixtures'],
        ]);

        $services = $container->services();

        // Written out by hand, tag and all — which is exactly what a real
        // contributing bundle has to do, since a reusable bundle's services are
        // not autoconfigured. If this fixture needed autowiring to work, the
        // seam would not work for the bundles it exists for.
        $services->set(FixtureNavigationSource::class)
            ->tag(UhifadhiLabsCanopyBundle::NAV_TAG)
            ->public();

        $services->set(FixtureAreaShellSource::class)
            ->public();

        // The host tells the crown which implementation answers the area seam,
        // by aliasing the id the crown looks for. An ALIAS, not a tagged
        // collection and not a config key: two things claiming to know an
        // area's tabs is the disagreement this bundle exists to prevent, and a
        // config key would put a class name in YAML where nothing checks it.
        $services->alias('canopy.area_shell_source', FixtureAreaShellSource::class);

        $services->alias('test.canopy.navigation', 'canopy.navigation')->public();
        $services->alias('test.canopy.area_shell', 'canopy.area_shell')->public();
        $services->alias('test.canopy.contract', 'canopy.contract')->public();
        $services->alias('test.canopy.theme', 'canopy.theme')->public();
    }
}
