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

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Uhifadhi\Shell\DependencyInjection\ShellConfiguration;

/**
 * The SHELL — the visible crown every uhifadhi page grows into.
 *
 * The seed is planted, the trunk carries, the branches are the modules and this
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
 * IT KNOWS NO MODULE BY NAME — the same rule the trunk holds, for the same
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
        // name over the crown of its own installation.
        $builder->setParameter(
            'shell.brand_name',
            \is_string($config['brand_name'] ?? null) ? $config['brand_name'] : 'Uhifadhi',
        );
        $builder->setParameter(
            'shell.home_route',
            \is_string($config['home_route'] ?? null) ? $config['home_route'] : 'dashboard_index',
        );

        // Which theme a visitor who has never chosen one gets. Light by default
        // because the platform's own default is light and a crown that opened
        // dark on a ranger's midday screen would be a worse first frame than a
        // wrong preference.
        $builder->setParameter(
            'shell.default_theme',
            \is_string($config['default_theme'] ?? null) ? $config['default_theme'] : 'light',
        );

        // Dev-only tooling — the socket gallery, which renders every block and
        // every token on one page so a change to the contract is visible before
        // it is shipped. Off in production: it is documentation that executes.
        $builder->setParameter('shell.dev_tools', true === ($config['dev_tools'] ?? false));
    }
}
