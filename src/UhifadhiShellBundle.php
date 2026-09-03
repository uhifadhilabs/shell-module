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

namespace Uhifadhi\Shell;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Uhifadhi\Shell\DependencyInjection\ShellConfiguration;

/**
 * The SHELL — the visible shell every uhifadhi page grows into.
 *
 * An application registers this bundle and every module renders into it. This
 * is what you see: the document, the shell (sidebar + top bar), the page frame
 * (breadcrumbs, page head, actions, tabs, flashes, body), the navigation seams
 * and the theme. A module writes a page by filling this bundle's blocks; it
 * writes no shell of its own, and it copies no `<div class="page">`.
 *
 * IT IS A CONTRACT, NOT A CONVENTION. The block names, the seam interfaces and
 * the theme tokens are a versioned, test-enforced API — Symfony-extension-point
 * grade. Renaming a block is a breaking change and has to be made as one: the
 * manifest is frozen in a test that fails if the list moves at all. This exists
 * because the alternative was in production — every module bundle re-declaring
 * `{% block content %}<div class="page">…` by copy, so that a change to the
 * frame reached the modules that happened to be remembered.
 *
 * IT REMEMBERS NOTHING. No entities, no repositories, no doctrine, no database.
 * A shell that needed a connection to draw a page would have acquired a
 * domain; Unit/BoundaryTest fails the build if one appears.
 *
 * IT KNOWS NO MODULE BY NAME — the same rule the seam holds, for the same
 * reason and enforced by the same kind of sweep. A module's row in the sidebar
 * and its card in the grid arrive as DATA, through the seams in src/Contract;
 * the shell renders whatever it is handed and recognises no slug.
 *
 * PHASE 1 — this repository is the scaffold and the RED contract. The shell,
 * the frame, the seams and the tokens arrive in phase 2 by EXTRACTION from the
 * host application, against the failing suite in tests/Phase2. What is here
 * today is the plug: the bundle registers, its config is keyed under "shell:",
 * and its template namespace resolves.
 */
final class UhifadhiShellBundle extends AbstractBundle
{
    /**
     * The shell's stylesheet, as a host and every module bundle must link it.
     *
     * Published as a constant for the reason every module bundle's own is: the
     * path is written in more than one place (the shell links it; a module base
     * extends the shell; a shared surface renders a contributor's sheet beside
     * it), and a path typed twice is a path that eventually differs.
     *
     * Under AssetMapper a bundle's public/ directory is exposed automatically
     * as `bundles/<lowercased bundle name>/`, content-versioned, with no
     * assets:install step.
     *
     * @see https://symfony.com/doc/current/frontend/asset_mapper.html
     */
    public const string STYLESHEET = 'bundles/uhifadhishell/shell.css';

    /**
     * The tab icon: the full masterbrand mark, as one SVG at every size.
     *
     * Shipped rather than left to the host because a document that declares no
     * icon is a document every browser asks `/favicon.ico` for, and a fresh
     * installation answered that with a 404 in its first minute. A deployment with a
     * brand of its own replaces the link through the head's sockets.
     */
    public const string FAVICON = 'bundles/uhifadhishell/favicon.svg';

    /**
     * THE SHELL'S ROUTES, as the one line an application writes to accept them.
     *
     * The shell ships a route — the welcome page's — the way WebProfilerBundle
     * ships `/_profiler`: as a RESOURCE this bundle never loads. An application
     * imports it in its own config/routes/shell.yaml and owns the decision:
     *
     *     shell:
     *         resource: '@UhifadhiShellBundle/config/routes/welcome.php'
     *
     * Published as a constant for the reason the stylesheet's path is: this
     * string is written in the recipe, in the skeleton, in the README and in
     * every installation, and a path typed twice is a path that eventually
     * differs.
     */
    public const string ROUTES = '@UhifadhiShellBundle/config/routes/welcome.php';

    /**
     * The AssetMapper namespace this bundle's assets/ directory is mapped to.
     *
     * It is the npm-style form of the composer package name, and it has to be:
     * Flex keys assets/controllers.json by '@'.<composer package name>, and
     * StimulusBundle resolves that key back to this directory. A different name
     * here is a controller the host cannot find.
     */
    public const string ASSET_NAMESPACE = '@uhifadhi/shell-module';

    /**
     * The prefix every one of this bundle's Stimulus controllers is addressed
     * by in a template — StimulusBundle's own normalisation of the namespace
     * above ('@' dropped, '/' and '_' to '-'), so `theme` is reached as
     * `uhifadhi--shell-module--theme`.
     */
    public const string CONTROLLER_PREFIX = 'uhifadhi--shell-module--';

    /**
     * The tag a bundle carries to contribute a NAV SECTION to the shell.
     *
     * Published as a constant because the shell is the end that COLLECTS it: a
     * contributor writes the string by hand in its own extension (a reusable
     * bundle's services are not autoconfigured), and a host or a test standing
     * in for the collector should not retype it. The shell renders whatever
     * arrives and reads no slug off it — see src/Contract.
     */
    public const string NAV_TAG = 'shell.nav_section';

    /** Config lives under "shell:", not the class-derived "uhifadhi_shell:". */
    protected string $extensionAlias = 'shell';

    public function configure(DefinitionConfigurator $definition): void
    {
        ShellConfiguration::define($definition->rootNode());
    }

    /**
     * THE SHELL'S OWN GLYPHS, registered under the `shell:` prefix.
     *
     * Four icons — the sidebar's collapse chevron, the tree's caret, the theme
     * toggle and the catalogue's lens marker — shipped with the bundle and
     * resolved from disk. This is the ring gate's lesson in the container: a
     * fresh installation has configured no icon set, and a shell
     * whose own chrome needed a network round trip to draw itself would not be
     * a shell that works out of the box.
     *
     * It is a PREFIX OF ITS OWN, not an addition to the host's set: a bundle
     * that quietly extended `lucide:` would be a bundle that decides what a
     * host's icon names mean.
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('ux_icons')) {
            $container->extension('ux_icons', [
                'icon_sets' => [
                    'shell' => ['path' => \dirname(__DIR__).'/assets/icons/shell'],
                ],
            ]);
        }

        // THE FURNITURE'S BEHAVIOUR, shipped with the furniture.
        //
        // The shell's own controls — the theme toggle, the sidebar's collapse,
        // the tree's carets — were markup here and behaviour in the host the
        // shell was extracted from, so on a fresh installation every one of them was
        // dead. A bundle contributes no importmap entry, but it does contribute
        // an AssetMapper path and a `symfony.controllers` block in
        // assets/package.json, which is how every symfony/ux package ships a
        // Stimulus controller (see TurboExtension::prepend). Flex writes the
        // host's assets/controllers.json on install; nothing is built.
        //
        // The bundle's public/ dir needs none of this: AssetMapper exposes it
        // as `bundles/uhifadhishell/` on its own.
        if ($builder->hasExtension('framework') && interface_exists(AssetMapperInterface::class)) {
            $container->extension('framework', [
                'asset_mapper' => [
                    'paths' => [
                        \dirname(__DIR__).'/assets' => self::ASSET_NAMESPACE,
                    ],
                ],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Static service wiring lives in a PHP config file (see config/services.php
        // for why PHP, not YAML). loadExtension keeps only the config-DRIVEN bits.
        $container->import('../config/services.php');

        // The wordmark beside the brand tile, and where the tile links. Neither
        // can be hardcoded: the shell is installed BY an application and does
        // not know its route names, and a deployment is entitled to its own
        // name over the shell of its own installation.
        $builder->setParameter(
            'shell.brand_name',
            \is_string($config['brand_name'] ?? null) ? $config['brand_name'] : 'Uhifadhi',
        );
        $builder->setParameter(
            'shell.home_route',
            \is_string($config['home_route'] ?? null) ? $config['home_route'] : 'dashboard_index',
        );

        // Which theme a visitor who has never chosen one gets. Light by default
        // because the platform's own default is light and a shell that opened
        // dark on a ranger's midday screen would be a worse first frame than a
        // wrong preference.
        $builder->setParameter(
            'shell.default_theme',
            \is_string($config['default_theme'] ?? null) ? $config['default_theme'] : 'light',
        );
    }
}
