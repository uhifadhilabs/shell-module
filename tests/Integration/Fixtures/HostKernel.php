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

namespace Uhifadhi\Shell\Tests\Integration\Fixtures;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Uhifadhi\Shell\Model\AreaTab;
use Uhifadhi\Shell\Model\NavSection;
use Uhifadhi\Shell\Tests\Integration\TestKernel;
use Uhifadhi\Shell\UhifadhiShellBundle;

/**
 * A STAND-IN HOST — and the specification's main claim, not a testing
 * convenience.
 *
 * This kernel is an application that implements the crown's seams and has
 * nothing else at all: no areas, no modules, no seam, no database, no user. If
 * the crown can be driven to a complete page by THIS, then the seams are real
 * seams rather than a polite name for reaching into the application, and the
 * "standalone" in the shell's charter is a fact about the code.
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

    /**
     * What the viewer is inside, in words — the middle segment of the page
     * title. The area seam answers it, because it is the same question the tabs
     * answer, asked for the title bar instead of the strip.
     */
    public static ?string $place = 'Test Area';

    /**
     * Flashes the host has pending, seeded into a real session by
     * {@see \Uhifadhi\Shell\Tests\Integration\ContractTestCase} before a render.
     *
     * EMPTY BY DEFAULT, and that is the honest default: a host usually has
     * nothing to say, and a stand-in host that always had a message pending
     * would make "an unfilled region leaves nothing behind" impossible to
     * assert. A test that is about flashes seeds one.
     *
     * @var list<array{string, string}> label, message
     */
    public static array $flashes = [];

    public static function reset(): void
    {
        self::$navSources = [];
        self::$areaTabs = [];
        self::$mirrorAreaTabsIntoNav = false;
        self::$place = 'Test Area';
        self::$flashes = [];
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
            ->tag(UhifadhiShellBundle::NAV_TAG)
            ->public();

        $services->set(FixtureAreaShellSource::class)
            ->public();

        // The host tells the crown which implementation answers the area seam,
        // by aliasing the id the crown looks for. An ALIAS, not a tagged
        // collection and not a config key: two things claiming to know an
        // area's tabs is the disagreement this bundle exists to prevent, and a
        // config key would put a class name in YAML where nothing checks it.
        $services->alias('shell.area_shell_source', FixtureAreaShellSource::class);

        $services->alias('test.shell.navigation', 'shell.navigation')->public();
        $services->alias('test.shell.area_shell', 'shell.area_shell')->public();
        $services->alias('test.shell.contract', 'shell.contract')->public();
        $services->alias('test.shell.theme', 'shell.theme')->public();
    }
}
