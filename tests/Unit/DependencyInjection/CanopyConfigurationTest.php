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

namespace Uhifadhi\Canopy\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Uhifadhi\Canopy\DependencyInjection\CanopyConfiguration;

/**
 * The config tree, checked the way a host meets it: through a Processor, with
 * the YAML a deployment actually writes.
 *
 * The tree is CLOSED. That is the whole point of the test — a layout bundle is
 * where configuration goes to breed, and an unknown key that is silently
 * ignored is how a deployment ends up believing it configured something.
 */
final class CanopyConfigurationTest extends TestCase
{
    public function testItDefaultsToTheLightCrownOfAnUhifadhiInstallation(): void
    {
        self::assertSame([
            'brand_name' => 'Uhifadhi',
            'home_route' => 'dashboard_index',
            'default_theme' => 'light',
            'dev_tools' => false,
        ], $this->process([]));
    }

    public function testADeploymentNamesItsOwnCrown(): void
    {
        $config = $this->process([['brand_name' => 'Kilimo', 'home_route' => 'app_home']]);

        self::assertSame('Kilimo', $config['brand_name']);
        self::assertSame('app_home', $config['home_route']);
    }

    /**
     * Light and dark are both first-class, and "system" is a third answer
     * rather than a flavour of either — a visitor who has told their operating
     * system which they want has already answered the question.
     */
    public function testBothThemesAndTheSystemPreferenceAreAcceptedAndNothingElseIs(): void
    {
        foreach (CanopyConfiguration::THEMES as $theme) {
            self::assertSame($theme, $this->process([['default_theme' => $theme]])['default_theme']);
        }

        $this->expectException(InvalidConfigurationException::class);
        $this->process([['default_theme' => 'sepia']]);
    }

    public function testAnEmptyBrandNameIsRefusedRatherThanRenderedAsAGap(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->process([['brand_name' => '']]);
    }

    /**
     * THE TREE IS CLOSED, and it stays small. There is deliberately no key
     * listing nav entries, no key listing area tabs and no key listing modules
     * — those arrive as data through the seams, because a YAML nav is a nav no
     * permission check ever reaches.
     */
    public function testAnUnknownKeyFailsLoudlyInsteadOfBeingIgnored(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->process([['nav' => [['label' => 'Areas']]]]);
    }

    /**
     * @param list<array<string, mixed>> $configs
     *
     * @return array<string, mixed>
     */
    private function process(array $configs): array
    {
        $tree = new TreeBuilder('canopy');
        CanopyConfiguration::define($tree->getRootNode());

        /** @var array<string, mixed> $processed */
        $processed = new Processor()->process($tree->buildTree(), $configs);

        return $processed;
    }
}
