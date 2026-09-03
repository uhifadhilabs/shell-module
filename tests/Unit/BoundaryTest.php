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

namespace Uhifadhi\Shell\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE BOUNDARIES, ENFORCED BY A SWEEP OF src/ AND templates/.
 *
 * These are cheap, crude tests that read the shipped source as text, and they
 * are the only kind that can catch what they catch. An extraction is a large
 * move under time pressure and "just this one reference, for now" is how a
 * layout acquires a domain — the more so here, because a template is the
 * easiest place in a codebase to type a module's name and the hardest place to
 * notice it later.
 *
 * Five rules:
 *
 *  1. THE SHELL NAMES NO MODULE. Not a slug, not a namespace, not in a
 *     template. A row in the sidebar and a card in the grid arrive as data.
 *  2. THE SHELL NAMES NO HOST. It is installed BY an application; it does not
 *     reach back into one.
 *  3. THE SHELL REMEMBERS NOTHING. No entities, no repositories, no doctrine,
 *     no database. A crown with entities has failed its boundary, and the
 *     absence of a postgres service in CI is the same statement in another file.
 *  4. THE SHELL ROUTES NOWHERE. It draws pages; it does not own URLs. The area
 *     URL space, its permission gates and its entity resolution are the host's.
 *  5. THE SHELL REQUIRES NO OTHER RING. Least obvious and most load-bearing:
 *     see the README's boundary ruling on why the crown does not depend on the
 *     seam even though it draws the seam's answers.
 */
final class BoundaryTest extends TestCase
{
    private const string ROOT = __DIR__.'/../..';

    /**
     * Real modules, plus the two the platform is likeliest to smuggle in:
     * "overview" (the pinned hub) and "map" (the first core module). Both are
     * flags a provider declares, never slugs a renderer recognises.
     */
    public static function moduleNames(): \Generator
    {
        foreach (['patrol', 'incident', 'roster', 'ingestion', 'storage', 'workflow', 'uhakiki', 'forest', 'overview'] as $name) {
            yield $name => [$name];
        }
    }

    /**
     * @param non-empty-string $name
     */
    #[DataProvider('moduleNames')]
    public function testTheShellKnowsNoModuleByName(string $name): void
    {
        $offenders = [];
        foreach (self::shippedSources() as $path => $code) {
            if (1 === preg_match('/\b'.preg_quote($name, '/').'\b/i', $code)) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, \sprintf(
            'The shell must not name the "%s" module — not in src/, and above all not in a template. '
            .'A nav row and a module card are data handed to the crown, never a slug it recognises.',
            $name,
        ));
    }

    public function testTheShellReachesIntoNoHostApplication(): void
    {
        $offenders = [];
        foreach (self::shippedSources() as $path => $code) {
            if (str_contains($code, 'Uhifadhi\\Entity')
                || str_contains($code, 'Uhifadhi\\Service')
                || str_contains($code, 'Uhifadhi\\Repository')
                || str_contains($code, 'Uhifadhi\\Controller')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The shell must not depend on a host application namespace.');
    }

    /**
     * THE SHELL REMEMBERS NOTHING. This is the rule that makes the crown
     * cheap: it can be booted, rendered and tested without a database, which is
     * why this repository's CI has no postgres service while every sibling's
     * does. The day an entity appears here, that stops being true and the whole
     * shape of the bundle changes with it.
     */
    public function testTheShellOwnsNoData(): void
    {
        self::assertDirectoryDoesNotExist(self::ROOT.'/src/Entity', 'Entities belong to the seam and the branches.');
        self::assertDirectoryDoesNotExist(self::ROOT.'/src/Repository', 'A crown reads what it is handed.');
        self::assertDirectoryDoesNotExist(self::ROOT.'/migrations', 'A crown owns no schema.');

        $offenders = [];
        foreach (self::phpSources() as $path => $code) {
            if (str_contains($code, 'Doctrine\\')
                || str_contains($code, 'ORM\\')
                || str_contains($code, 'EntityManager')
                || str_contains($code, 'DATABASE_URL')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The shell draws; it does not remember.');
        self::assertArrayNotHasKey('doctrine/orm', self::composerRequire());
        self::assertArrayNotHasKey('doctrine/doctrine-bundle', self::composerRequire());
    }

    /**
     * THE SHELL ROUTES NOWHERE. The grid's UI is the crown's (see the README);
     * the grid's URL is not. `/areas/{uuid}/modules` is the host's URL space,
     * gated by the host's permissions and resolving the host's area entity — a
     * controller here would drag all three across the boundary to save one
     * template include.
     */
    public function testTheShellOwnsNoUrls(): void
    {
        self::assertDirectoryDoesNotExist(self::ROOT.'/src/Controller', 'The crown draws pages; the host owns their URLs.');
        self::assertFileDoesNotExist(self::ROOT.'/config/routes.php');
        self::assertFileDoesNotExist(self::ROOT.'/config/routes.yaml');

        $offenders = [];
        foreach (self::phpSources() as $path => $code) {
            if (str_contains($code, 'Symfony\\Component\\Routing\\Attribute\\Route')
                || str_contains($code, 'AbstractController')) {
                $offenders[] = $path;
            }
        }

        self::assertSame([], $offenders, 'The shell exposes blocks and seams; it registers no route.');
    }

    /**
     * THE SHELL REQUIRES NO OTHER RING, and this is the ruling worth arguing —
     * the README argues it at length. The short form: the crown draws the
     * seam's answers but does not read them. They arrive already composed,
     * because composing them needs an area, a viewer and a department lens,
     * none of which the seam has either. A require here would make the crown
     * unusable on an installation with no module seam, and would put seam
     * entities inside templates, which is exactly where a `module.getSlug()`
     * comparison gets typed.
     */
    public function testTheShellRequiresNoOtherRingOfTheTree(): void
    {
        $uhifadhiRequires = array_filter(
            array_keys(self::composerRequire()),
            static fn (string $package): bool => str_starts_with($package, 'uhifadhi/'),
        );

        self::assertSame([], array_values($uhifadhiRequires), 'The shell is standalone: data reaches it through its seams.');
    }

    /**
     * FLAT FOLDERS, BY TECHNICAL KIND. src/Domain and its relatives are banned
     * across the tree; the crown has no domain to put in one anyway. templates/
     * is not an exception to the rule — it is not a domain folder, it is this
     * bundle's entire subject, and it must exist.
     */
    public function testItKeepsTheFlatFolderConvention(): void
    {
        self::assertDirectoryExists(self::ROOT.'/templates', 'templates/ is the shell\'s heart, not an afterthought.');

        foreach (['Domain', 'Application', 'Infrastructure', 'UI', 'Presentation'] as $banned) {
            self::assertDirectoryDoesNotExist(self::ROOT.'/src/'.$banned, \sprintf('src/%s is banned: folders are named by technical kind.', $banned));
        }
    }

    /**
     * @return array<string, string> relative path => contents (php + twig)
     */
    private static function shippedSources(): array
    {
        return self::phpSources() + self::templates();
    }

    /**
     * @return array<string, string>
     */
    private static function phpSources(): array
    {
        return self::read(self::ROOT.'/src', 'src', ['php']);
    }

    /**
     * @return array<string, string>
     */
    private static function templates(): array
    {
        return self::read(self::ROOT.'/templates', 'templates', ['twig']);
    }

    /**
     * @return array<string, string>
     */
    private static function composerRequire(): array
    {
        $json = file_get_contents(self::ROOT.'/composer.json');
        self::assertIsString($json);
        $manifest = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $require = $manifest['require'] ?? [];
        self::assertIsArray($require);

        /** @var array<string, string> $require */
        return $require;
    }

    /**
     * @param list<string> $extensions
     *
     * @return array<string, string> relative path => contents
     */
    private static function read(string $directory, string $label, array $extensions): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!\in_array($file->getExtension(), $extensions, true)) {
                continue;
            }
            $code = file_get_contents($file->getPathname());
            if (false === $code) {
                continue;
            }
            $files[$label.'/'.substr($file->getPathname(), \strlen($directory) + 1)] = $code;
        }

        ksort($files);

        return $files;
    }
}
