# uhifadhi/canopy-module

The **canopy**: the visible crown of an uhifadhi installation — the shell, the
page frame, the navigation seams and the theme every module's pages mount into.
A [uhifadhi](https://github.com/uhifadhilabs) platform bundle.

> **Status: phase 1 — the scaffold and the RED contract.** The crown itself is
> still in the [uhifadhi](https://github.com/uhifadhilabs/uhifadhi) host,
> working. It arrives here in phase 2 by extraction, against the failing
> specification in `tests/Phase2`. See [How this is being
> built](#how-this-is-being-built).

## Contents

- [The tree](#the-tree)
- [Charter](#charter)
- [How this is being built](#how-this-is-being-built)
- [The socket contract](#the-socket-contract)
  - [1. The named sockets](#1-the-named-sockets)
  - [2. The nav seam](#2-the-nav-seam)
  - [3. The area shell](#3-the-area-shell)
  - [4. The theme contract](#4-the-theme-contract)
  - [5. The module grid](#5-the-module-grid)
- [Changing the contract](#changing-the-contract)
- [Boundaries: what the canopy is not](#boundaries-what-the-canopy-is-not)
- [Why the canopy does not require the trunk](#why-the-canopy-does-not-require-the-trunk)
- [What is here](#what-is-here)
- [Installation](#installation)
- [Configuration](#configuration)
- [Development](#development)
- [License](#license)

## The tree

Uhifadhi is structured like the thing it protects:

> **`uhifadhi/seed`** (planted once) → **`trunk-module`** (the seam runtime every
> module registers with) → **branches** (the modules) → **`canopy-module`** (this
> repository: the visible crown).

The seed is copied once and is then yours forever. Everything above it is a
bundle, updated through composer. This ring is the one you can see.

## Charter

**The canopy shows; it does not carry.** It owns four things and no more:

- **The frames** — the document, the shell (sidebar + top bar) and the page
  frame (breadcrumbs, page head, actions, tabs, flashes, body). Three rungs of
  one ladder; a page steps onto whichever it needs.
- **The seams** — how a nav row and an area's tab strip get their content from
  outside, without the crown knowing what an area or a module is.
- **The theme** — one token set, two complete palettes, both first-class.
- **The shared pictures** — the module grid and the cards it is made of: the
  drawings of other rings' answers that would otherwise be redrawn per page.

**It is a contract, not a convention.** Block names, seam interfaces and theme
tokens are a versioned, test-enforced API — Symfony-extension-point grade. This
bundle exists because the alternative was in production: five bundles each
re-declaring `{% block content %}<div class="page">…` by copy, each with its own
flash markup, so that a change to the page frame reached the copies somebody
remembered. A convention is a contract that nothing checks.

**It remembers nothing.** No entities, no repositories, no doctrine, no
database. This is why this repository's CI is the only one in the tree with no
postgres service: a crown with entities has failed its boundary. It is also why
the whole test suite is "render this and look at it".

**It knows no module by name.** The trunk's rule, inherited, and enforced by the
same kind of sweep — extended to `templates/`, because a template is the easiest
place in a codebase to type a module's name and the hardest place to notice it
later. A row in the sidebar and a card in the grid arrive as data.

**Zero of everything is a working installation.** No nav sources, no tabs, no
modules: the crown renders a sidebar with a brand and no rows, and a page. A
layout that only works once the application is finished is not a layout.

## How this is being built

The crown already exists, working, inside the host application:
`templates/layout.html.twig`, the ported sidebar, the theme in
`assets/styles/app.css`, the area tab strip, the module grid. This repository
will extract it — and because this project is test-first, the specification is
written *before* the move rather than after it: a suite that names templates,
blocks, tokens, interfaces and service ids that do not exist yet, red by design,
in a suite of its own so that "red by design" and "broken" can never be
confused.

```bash
composer check        # cs -> phpstan max -> the SCAFFOLD suite. CI gates on this.
composer test:phase2  # the contract. Red until the extraction lands.
```

**Three deletions mark the end of phase 2**, and until all three are gone the
extraction is not finished:

1. the `tests/Phase2` exclusion in `phpstan.dist.neon`
2. the `phase2` testsuite in `phpunit.dist.xml` — its files move into
   `tests/{Unit,Integration}`
3. the `continue-on-error` step in `.github/workflows/ci.yml`

## The socket contract

Every row below is a test.

### 1. The named sockets

Twenty-three blocks, in three frames that inherit from one another. The list is
typed out literally in `Phase2/Sockets/BlockContractTest::contractV1()` and
checked against `LayoutContract::BLOCKS`, so a rename fails the build from both
ends: the manifest disagrees with the frozen list, or the templates disagree
with the manifest. There is no way to move a socket without editing that test.

**The document** — `@UhifadhiLabsCanopy/document.html.twig`. Symfony's own four
names, unchanged; a module that knows Twig already knows them.

| Socket | Filled by |
|---|---|
| `title` | every page — the crown composes `<page> — <area> — <brand>` |
| `stylesheets` | module bases, calling `parent()` **first** |
| `javascripts` | module bases, calling `parent()` **last** when a classic script must beat the deferred importmap (the Leaflet rule) |
| `importmap` | nobody, normally — here so a host can |
| `body` | nobody: the shell owns it |

**The shell** — `@UhifadhiLabsCanopy/shell.html.twig`. Furniture. A module fills
none of these; they are the host's, because they need the viewer, the areas and
whether this response is an impersonation.

| Socket | Filled by |
|---|---|
| `canopy_banner` | host — impersonation, maintenance, outage |
| `canopy_sidebar` | host — replace the whole aside (rare) |
| `canopy_sidebar_brand` | host — the mark and the wordmark |
| `canopy_sidebar_nav` | host — overriding this opts out of the nav seam |
| `canopy_sidebar_footer` | host — settings, version, support |
| `canopy_topbar` | host — replace the whole top bar |
| `canopy_topbar_actions` | host — alerts, theme toggle, the user pill |
| `canopy_main` | host — everything right of the nav |
| `content` | **the compatibility socket** — see below |
| `canopy_footer` | host — the one line under every page |

**The page frame** — `@UhifadhiLabsCanopy/page.html.twig`. The module author's
sockets, and the reason the bundle exists.

| Socket | Filled by |
|---|---|
| `canopy_breadcrumbs` | module — the trail, as text; the frame styles it |
| `canopy_page_head` | module — replace title + subtitle + actions wholesale |
| `canopy_page_title` | module — the `h1` |
| `canopy_page_subtitle` | module — optional, and *genuinely* optional: empty renders no element |
| `canopy_page_actions` | module — the buttons at the top right |
| `canopy_page_tabs` | module/host — defaults to the area shell (§3) |
| `canopy_flashes` | module — overridable, but the default is right and an override is a bug report |
| `canopy_page` | module — **the body**, the one socket a page must fill |

`content` is in the contract on purpose and is the one socket named for
compatibility rather than clarity: every host page and every module bundle fills
it today against `layout.html.twig`. Renaming it would break five repositories in
one commit — exactly the casual rewiring this bundle exists to end. It stays as
the shell's main slot; the page frame fills it with the framed page, and a
full-bleed screen fills it directly.

Beyond the frames, the contract also pins **behaviour**: the vertical order
inside `.page` (crumb → page head → tabs → flashes → body), that an unfilled
socket leaves no empty element behind, that flashes are rendered once by the
frame so every module says "saved" the same way, and that the crown's stylesheet
always lands before a module's.

### 2. The nav seam

The crown owns the nav's **shape** — sections, rows, the location tree, carets,
the current-row treatment, the collapsed rail. It owns none of its **content**.
Content arrives through `NavigationSourceInterface`, tagged `canopy.nav_section`:

- the **host** implements one, and that is where trunk data enters the crown. It
  has the areas, the viewer, the permission voters and the trunk's per-area
  ledger; folding those four into "these rows, in this order" is a reading for a
  person on a page.
- a **module bundle** may implement one too, for the rare platform-wide row that
  belongs to nobody's area.

Pinned by test: sections come out in declared-position order with registration as
the tie-break; **gating is the source's job** (the crown holds no
`AuthorizationChecker` and calls `is_granted` on nothing — a withheld row is
absent, never hidden); a row with no destination renders inert rather than
disappearing; folding is a class, never an omission (a caret that folds by not
rendering has nothing to reopen); **exactly one row is current** or the crown
refuses; and the nav is read live per render, so switching a module off takes its
row with it the same day.

### 3. The area shell

The honest split, decided rather than fudged:

| The crown owns | The crown owns *not* |
|---|---|
| that sibling screens are an underlined tab strip | Overview, Modules, Zones, Settings |
| that it sits between the page head and the body | which of them this viewer has |
| that exactly one tab is lit | which one is current |
| that an unavailable tab is **absent, not disabled** | the gating decision behind that |
| that one tab is no strip at all | |

Tabs arrive through `AreaShellSourceInterface`, which the host aliases to
`canopy.area_shell_source`. Today the list is hardcoded **twice** in the host —
`dashboard/_area_tabs.html.twig` and `SidebarRuntime::tabs()` — and the two have
to be edited together, which is the tell. One source, two renderings, and a test
that they cannot disagree.

Two things this fixes rather than ports: a module page inside an area currently
loses the area's tabs entirely, because the strip lives in a partial the host
includes by hand; under the frame the strip is the *default* content of
`canopy_page_tabs`, so a module page that fills only its body still shows you
where you are. And "absent, not disabled" is now a rule the value object
enforces — `AreaTab` has no url-less form, so there is nothing to grey out.

*Why not an area module?* The strip has no behaviour to own: it is markup plus a
rule about lighting, and a bundle whose entire content is one Twig partial is a
dependency, not a ring. If an area module is ever planted, it implements this
source; it does not take the strip.

### 4. The theme contract

Twenty-three tokens, frozen the same way the blocks are, because a module's
stylesheet is written against these names: `rgb(var(--c-p1))` in a patrol card is
a promise the crown made.

| Group | Tokens |
|---|---|
| surfaces | `--c-cv` `--c-p1` `--c-p2` `--c-raised` |
| ink (three weights, and only three) | `--c-tx` `--c-fog` `--c-dim` |
| accent | `--c-acc` `--c-accT` |
| state | `--c-ok` `--c-warn` `--c-fail` |
| edges and depth | `--c-ln` `--c-ln2` `--glass` `--shadow` `--accGlow` |
| brand (derived) | `--logo-tile` `--logo-child` `--logo-accent` |
| type | `--font-display` `--font-body` `--font-mono` |

**Light and dark are both first-class** — two complete palettes, not a filter over
one. Every token in the first five groups must carry a dark value, and the test
is driven by the same frozen list, so a token cannot be added to light alone. The
brand tokens must **not** be restated per theme: they ride the channels of
`--c-acc` and `--c-cv`, landing deep jade on the light canvas and mint on the
dark one with no override, and a second definition is a second place the brand
colour is decided.

Also pinned: the dark selector is `html.dark` (module sheets write it too, so a
move to a data attribute would silently unstyle the tree); the theme is resolved
by an inline script in `<head>` **before first paint**, not by a controller that
connects after the first frame — a visitor who chose dark must not be shown a
white page first, and today they are; `system` is a real third answer, not a
synonym for light; and any custom property the crown declares that is not on the
list must be prefixed `--_` as private, because a token a module can read is a
token a module will read.

Deliberately **not** here: the map chrome tokens (`--z-ink`, `--z-paper`,
`--z-imagery`, `--z-aoi`). They belong to `uhifadhi/map-module`, the ring that
owns how a layer draws; a legend palette in the crown could not be changed
without a crown release.

### 5. The module grid

**The ruling: the crown claims the picture, not the grouping, not the URL, and
not the customize screen.** The trunk declined the grid and named the canopy as
its claimant, so this had to be answered rather than inherited. The independent-life
test splits it:

- **the picture lives alone.** Cards in category groups, a status chip, a lens
  marker on the group a department leads, an empty state — a layout, complete
  given a list of groups, and the same layout wherever it appears (the area's
  Modules tab, a department page, a future search result). One implementation, at
  `@UhifadhiLabsCanopy/_module_grid.html.twig`.
- **the grouping does not.** Which cards, in which groups, in which order, and
  which department leads one is a reading of the catalogue for a particular
  viewer on a particular area — it needs the area, the viewer and the department
  lens, none of which the crown has or should acquire. The host's
  `ModuleGridService` stays where it is.
- **the URL does not.** `/areas/{uuid}/modules` is the host's URL space, gated by
  the host's `module.view`, resolving the host's area entity. A controller here
  would drag all three across the boundary to save an include.
- **the customize screen is not the grid's neighbour but its opposite.** It is a
  form that *writes* per-area install state: a POST, a CSRF token, an
  authorization decision, a flush. The crown has no writes anywhere in it, and
  the first one would end "the layout renders from a fixture with no database".
  A test greps the templates for `method="post"` and `csrf_token`.

## Changing the contract

The sockets and the tokens are a public API. The policy is short, and it is
enforced by the frozen lists refusing to agree with anything else:

- **Adding** a socket or a token: append it to the frozen list in the test, in
  the group it belongs to, with the comment saying who fills it; bump the minor
  version. `LayoutContract::VERSION` exists so a module bundle can require a
  crown that has the sockets it fills.
- **Renaming**: major version, a deprecation cycle, and the old name kept as an
  alias block for one release. `content` is the worked example.
- **Removing**: as renaming, plus a note in this section.

Editing a frozen list to make a build pass is the failure mode the lists exist to
catch.

## Boundaries: what the canopy is not

Concretely, this bundle ships **no entities, no repositories, no doctrine
dependency, no migrations, no controllers and no routes**, and
`tests/Unit/BoundaryTest` fails the build if that changes. It also ships no
`src/Domain`, `src/Application`, `src/Infrastructure` or `src/UI` — folders are
named by technical kind. `templates/` is not an exception to that rule: it is not
a domain folder, it is this bundle's entire subject.

## Why the canopy does not require the trunk

This is the ruling worth arguing, because the obvious defence runs the other way.

The Symfony analogy offered for a canopy → trunk require is `twig-bundle`
depending on `framework-bundle`: a higher ring may depend on a lower one, and
nothing about the tree forbids it. That analogy is real but it does not reach.
`twig-bundle` depends on `framework-bundle` for **machinery** — the kernel, the
config pass, the container conventions. The crown would be requiring the trunk to
read **domain data**.

Three consequences decide it:

1. **The data is not usable raw anyway.** A nav row needs the area, the viewer
   and the permission decision; a grid card needs the department lens. The trunk
   has none of those, so *something* has to compose the answer before the crown
   can draw it — and whatever composes it can hand over a value object as easily
   as the crown could fetch a row. The require buys nothing.
2. **Entities in templates are interrogated.** The moment a trunk entity is in
   scope inside a Twig file, somebody writes `{% if module.slug == 'overview' %}`,
   and the module-blindness both rings promise is gone. A `ModuleCard` value
   object cannot be interrogated that way — there is nothing on it to branch on
   that is not also a thing every card has.
3. **A crown that requires a seam runtime cannot crown an installation that has
   no modules.** `tests/Integration/TestKernel` is the proof: framework + twig +
   this bundle, booting and rendering, with no trunk under it at all.

So: **`composer.json` requires no `uhifadhi/*` package**, and a test asserts it,
because this is precisely the kind of ruling that gets quietly reversed by one
`composer require` during an extraction.

## What is here

| Piece | File |
|---|---|
| The Symfony plug, the stylesheet path, the nav tag | `src/UhifadhiLabsCanopyBundle.php` |
| Config tree (`canopy:`) | `src/DependencyInjection/CanopyConfiguration.php` |
| Static service wiring, and the published ids | `config/services.php` |
| The frozen manifest (phase 2) | `src/Contract/LayoutContract.php` |
| The seams (phase 2) | `src/Contract/` |
| The shapes that cross them (phase 2) | `src/Model/` |
| The frames and partials (phase 2) | `templates/` |
| The token set (phase 2) | `public/canopy.css` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: two seam implementations and nothing else | `tests/Phase2/Fixtures/HostKernel.php` |

## Installation

```bash
composer require uhifadhi/canopy-module
```

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`UhifadhiLabs\Canopy\UhifadhiLabsCanopyBundle` to `config/bundles.php`. A host
then implements the two seams and points the crown at them:

```php
// config/services.php
$services->set(App\Canopy\HostNavigation::class)->tag('canopy.nav_section');
$services->alias('canopy.area_shell_source', App\Canopy\AreaShell::class);
```

…and its pages extend the frame:

```twig
{% extends '@UhifadhiLabsCanopy/page.html.twig' %}
{% block canopy_page_title %}Zones{% endblock %}
{% block canopy_page %}…{% endblock %}
```

## Configuration

```yaml
# config/packages/canopy.yaml
canopy:
    brand_name: Uhifadhi           # the wordmark beside the brand tile
    home_route: dashboard_index    # where the tile links
    default_theme: light           # light | dark | system
    dev_tools: false               # the socket gallery (when@dev / when@test)
```

Every key has a default and the tree is closed, so an unknown key fails loudly
rather than being ignored. Each one is something the crown genuinely **cannot**
know: the deployment's name, the host's route names, a first-visit preference.
There is deliberately **no key listing nav entries, area tabs or modules** —
those arrive as data through the seams, because a YAML nav is a nav no permission
check ever reaches.

## Development

```bash
composer install
composer check   # cs:check -> phpstan (max) -> the scaffold suite
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** This repository is that rule taken literally: the
  whole contract is written before a line of the crown exists.
- `tests/Integration/TestKernel.php` is framework + twig + ux-icons + this
  bundle, with `strict_variables` on, and **no database** — that is the boundary,
  not a convenience.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle crowns. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.
