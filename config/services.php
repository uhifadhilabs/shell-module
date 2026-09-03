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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * The bundle's static service wiring.
 *
 * PHP (not YAML) on purpose: a reusable bundle must not force symfony/yaml onto
 * hosts, and FQCN references stay refactor-safe and phpstan-checked. Imported by
 * UhifadhiCanopyBundle::loadExtension(), which keeps only the config-DRIVEN
 * definitions.
 *
 * Everything defined here is defined EXPLICITLY — no autowire(), no autoconfigure(),
 * and ids prefixed with the bundle alias — because this bundle is installed by other
 * projects via Composer, which is what Symfony calls a reusable bundle:
 *
 *   "Services should not use autowiring or autoconfiguration. Instead, all
 *    services should be defined explicitly."
 *   "If the bundle defines services, they must be prefixed with the bundle alias."
 *   — https://symfony.com/doc/current/bundles/best_practices.html
 *
 * A NOTE ON TWIG, because this bundle is mostly Twig and the split is not
 * decoration: an EXTENSION is constructed as soon as the `twig` service is
 * built, and the image build does exactly that (asset-map:compile fires the
 * asset-compile event and UX Icons warms its cache off it). An extension
 * holding anything that reads a request or a repository therefore breaks the
 * BUILD, not a page. So the canopy ships a thin extension that declares
 * functions and a RUNTIME that is constructed lazily, on the first call — i.e.
 * only when a template is actually rendered. The host learned this the hard
 * way with its sidebar; the crown inherits the lesson, not the bug.
 *
 * Empty in phase 1, and on purpose. The crown arrives by EXTRACTION from the
 * host in phase 2, against the failing specification in tests/Phase2; that
 * specification names the service ids it will land under, which is the whole
 * contract this file has to satisfy:
 *
 *   canopy.contract          the frozen socket manifest: blocks, tokens, seams
 *   canopy.navigation        the nav tree the shell renders, from tagged sources
 *   canopy.area_shell        the area tab strip's model, from its source
 *   canopy.theme             which theme this response opens in
 *   canopy.module_grid       the catalogue picture: groups, cards, pills
 *   canopy.twig.extension    declares canopy_* functions and nothing else
 *   canopy.twig.runtime      builds them, lazily, on first render
 *   canopy.gallery           the dev-only socket gallery (canopy.dev_tools)
 *
 * The file exists so the first of them lands in the right place, in the right
 * style, rather than being autowired into the host's habits.
 */
return static function (ContainerConfigurator $container): void {
    $container->services();
};
