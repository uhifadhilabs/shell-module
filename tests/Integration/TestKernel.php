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

namespace Uhifadhi\Shell\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Icons\UXIconsBundle;
use Uhifadhi\Shell\UhifadhiShellBundle;

/**
 * THE SEED, PLUS THE SHELL, AND NOTHING ELSE: framework + twig + ux-icons +
 * this bundle. Note what is missing and stays missing — doctrine. The crown is
 * the one ring in the tree that can be booted without a database, and that is
 * not a testing convenience: it is the boundary. A layout that cannot render
 * without a connection has stopped being a layout.
 *
 * Note also what is missing and is NOT the boundary: a seam. The shell does
 * not require one (see the README's boundary ruling) — module rows and module
 * cards reach it as data, through the seams, from whoever composed them. This
 * kernel proves it by booting a crown with no seam runtime under it at all.
 */
class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new UXIconsBundle();
        yield new UhifadhiShellBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
        ]);

        // strict_variables ON, in the bundle's own test host, because the crown
        // is a set of templates other people fill: a page that hands the frame
        // an undefined variable must fail here rather than render a hole.
        $container->extension('twig', [
            'strict_variables' => true,
        ]);

        // NO NETWORK, for the reason there is no database: this suite renders,
        // and a render that reaches the internet is a render that fails in a
        // tunnel. Icons the crown ships resolve from its own `shell:` set; an
        // icon a FIXTURE names (a host's own set, which this kernel does not
        // have) resolves to nothing rather than to an HTTP request, because
        // whether a host's icons are installed is not this bundle's contract.
        $container->extension('ux_icons', [
            'iconify' => ['enabled' => false],
            'ignore_not_found' => true,
        ]);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/shell-module-tests/cache/'.$this->getEnvironment().'/'.static::class;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/shell-module-tests/log';
    }
}
