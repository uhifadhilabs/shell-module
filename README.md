# uhifadhi/shell-module

The **shell**: what an uhifadhi installation looks like — the document, the page
frame, the navigation seams and the theme every module's pages mount into.
A [uhifadhi](https://github.com/uhifadhilabs) platform bundle.

> Installs with `composer require uhifadhi/shell-module`, registers via Flex,
> and provides three page frames with twenty-three named blocks, two navigation
> seams, a twenty-three token theme in light and dark, and the module grid.
> Needs no database and no other uhifadhi package.

## Contents

- [The architecture](#the-architecture)
- [What it owns](#what-it-owns)
- [What it guarantees](#what-it-guarantees)
  - [The named sockets](#the-named-sockets)
  - [How a module fills them](#how-a-module-fills-them)
  - [The nav seam](#the-nav-seam)
  - [The area shell](#the-area-shell)
  - [The theme](#the-theme)
  - [The module grid](#the-module-grid)
  - [Changing any of it](#changing-any-of-it)
- [Boundaries: what the shell is not](#boundaries-what-the-shell-is-not)
  - [Why the shell does not require the seam](#why-the-shell-does-not-require-the-seam)
- [What is here](#what-is-here)
- [Installation](#installation)
  - [What a fresh installation shows](#what-a-fresh-installation-shows)
- [Configuration](#configuration)
- [Development](#development)
- [License](#license)

## The architecture

**Uhifadhi is one skeleton and a set of bundles.**
[`uhifadhi/uhifadhi`](https://github.com/uhifadhilabs/uhifadhi) is the project
skeleton — copied once, never updated; everything else arrives as a bundle,
updated forever. A module **registers with the seam**
([`uhifadhi/seam-module`](https://github.com/uhifadhilabs/seam-module)) and
**renders in the shell** ([`uhifadhi/shell-module`](https://github.com/uhifadhilabs/shell-module)
— this repository); everything a deployment can do — patrols, incidents,
rosters — is a module.

This is the package you can see.

## What it owns

**The shell shows; it does not carry.** It owns four things and no more:

- **The frames** — the document, the shell (sidebar + top bar) and the page
  frame (breadcrumbs, page head, actions, tabs, flashes, body). Three rungs of
  one ladder; a page steps onto whichever it needs.
- **The seams** — how a nav row and an area's tab strip get their content from
  outside, without the shell knowing what an area or a module is.
- **The theme** — one token set, two complete palettes, both first-class.
- **The shared pictures** — the module grid and the cards it is made of: the
  drawings of answers composed elsewhere, which would otherwise be redrawn once
  per page that needs them.

## What it guarantees

**It is a contract, not a convention.** Block names, seam interfaces and theme
tokens are a versioned, test-enforced API — Symfony-extension-point grade. This
bundle exists because the alternative was in production: five bundles each
re-declaring `{% block content %}<div class="page">…` by copy, each with its own
flash markup, so that a change to the page frame reached the copies somebody
remembered. A convention is a contract that nothing checks.

**It remembers nothing.** No entities, no repositories, no doctrine, no
database. This is why this repository's CI is the only one in the platform with
no postgres service: a shell with entities has failed its boundary. It is also
why the whole test suite is "render this and look at it".

**It knows no module by name.** The seam's rule, inherited, and enforced by the
same kind of sweep — extended to `templates/`, because a template is the easiest
place in a codebase to type a module's name and the hardest place to notice it
later. A row in the sidebar and a card in the grid arrive as data.

**Zero of everything is a working installation.** No nav sources, no tabs, no
modules: the shell renders a sidebar with a brand and no rows, and a page. A
layout that only works once the application is finished is not a layout.

Every row below is a test.

### The named sockets

Twenty-three blocks, in three frames that inherit from one another. The list is
typed out literally in `Integration/Sockets/BlockContractTest::contractV1()` and
checked against `LayoutContract::BLOCKS`, so a rename fails the build from both
ends: the manifest disagrees with the frozen list, or the templates disagree
with the manifest. There is no way to move a socket without editing that test.

**The document** — `@UhifadhiShell/document.html.twig`. Symfony's own four
names, unchanged; a module that knows Twig already knows them.

| Socket | Filled by |
|---|---|
| `title` | every page — the shell composes `<page> — <area> — <brand>` |
| `stylesheets` | module bases, calling `parent()` **first** |
| `javascripts` | module bases, calling `parent()` **last** when a classic script must beat the deferred importmap (the Leaflet rule) |
| `importmap` | nobody, normally — here so a host can |
| `body` | nobody: the shell owns it |

**The shell** — `@UhifadhiShell/shell.html.twig`. Furniture. A module fills
none of these; they are the host's, because they need the viewer, the areas and
whether this response is an impersonation.

| Socket | Filled by |
|---|---|
| `shell_banner` | host — impersonation, maintenance, outage |
| `shell_sidebar` | host — replace the whole aside (rare) |
| `shell_sidebar_brand` | host — the mark and the wordmark |
| `shell_sidebar_nav` | host — overriding this opts out of the nav seam |
| `shell_sidebar_footer` | host — settings, version, support |
| `shell_topbar` | host — replace the whole top bar |
| `shell_topbar_actions` | host — alerts, theme toggle, the user pill |
| `shell_main` | host — everything right of the nav |
| `content` | **the compatibility socket** — see below |
| `shell_footer` | host — the one line under every page |

**The page frame** — `@UhifadhiShell/page.html.twig`. The module author's
sockets, and the reason the bundle exists.

| Socket | Filled by |
|---|---|
| `shell_breadcrumbs` | module — the trail, as text; the frame styles it |
| `shell_page_head` | module — replace title + subtitle + actions wholesale |
| `shell_page_title` | module — the `h1` |
| `shell_page_subtitle` | module — optional, and *genuinely* optional: empty renders no element |
| `shell_page_actions` | module — the buttons at the top right |
| `shell_page_tabs` | module/host — defaults to the area shell |
| `shell_flashes` | module — overridable, but the default is right and an override is a bug report |
| `shell_page` | module — **the body**, the one socket a page must fill |

`content` is in the contract on purpose and is the one socket named for
compatibility rather than clarity: every host page and every module bundle fills
it today against `layout.html.twig`. Renaming it would break five repositories in
one commit — exactly the casual rewiring this bundle exists to end. It stays as
the shell's main slot; the page frame fills it with the framed page, and a
full-bleed screen fills it directly.

Beyond the frames, the contract also pins **behaviour**: the vertical order
inside `.page` (crumb → page head → tabs → flashes → body), that an unfilled
socket leaves no empty element behind, that flashes are rendered once by the
frame so every module says "saved" the same way, and that the shell's stylesheet
always lands before a module's.

### How a module fills them

The whole point, in the shortest page that works. A module bundle's page extends
the frame, fills sockets, and types no furniture:

```twig
{# templates/sightings/index.html.twig (your bundle) #}
{% extends '@UhifadhiShell/page.html.twig' %}

{% block shell_page %}
    <p>Everything else — the sidebar, the top bar, the brand, the tab strip of
       whatever place this page is inside, the flashes, the footer — is already
       around this paragraph.</p>
{% endblock %}
```

That is a complete, correct platform page. `shell_page` is the only socket a
page **must** fill. A fuller one adds the parts it has and leaves out the parts
it does not — an empty block renders no element, so there is nothing to pay for
a subtitle you never wrote:

```twig
{# templates/sightings/index.html.twig (your bundle) #}
{% extends '@UhifadhiShell/page.html.twig' %}

{% block title %}Sightings{% endblock %}

{% block shell_breadcrumbs %}
    <a href="{{ path('dashboard_index') }}">uhifadhi</a> /
    <a href="{{ area_url }}">{{ area.name|lower }}</a> / sightings
{% endblock %}

{% block shell_page_title %}Sightings{% endblock %}
{% block shell_page_subtitle %}Every record this month, by observer.{% endblock %}

{% block shell_page_actions %}
    <a class="cta" href="{{ path('sighting_new') }}">{{ ux_icon('lucide:plus') }}New</a>
{% endblock %}

{% block shell_page %}
    {{ include('@Sightings/_table.html.twig') }}
{% endblock %}
```

Four rules a module author needs and nothing else:

**Attributes on `<body>` are the one variable, not a block.** A block cannot put
them there, so set `shell_body_attributes` at the top of your template, outside
any block, and the document's tag picks it up — that is how a platform-wide
setting the map controllers read rides on a document the shell owns. It is
emitted as markup, so escape your own values.

**Do not write `<div class="page">`, a `.crumb`, a `.pghead` or a flash loop.**
The frame owns all four. Every one of them was, at some point, copied into a
module bundle from whichever host template was open at the time — which is the
reason this package exists.

**Your stylesheet goes after the shell's, and `parent()` is what puts it there.**

```twig
{# templates/sightings/_base.html.twig (your bundle) #}
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('bundles/sightings/sightings.css') }}">
{% endblock %}
```

Call `parent()` **first**: your rules are written to override the shell's
tokens and furniture, and a base that forgets it ships a page with no theme at
all. Write your colours as `rgb(var(--c-acc))` and your dark rules as
`html.dark .your-card { … }` — both are contract, and the theme test is what
keeps them true. In `javascripts`, `parent()` goes **last** when a classic
script must run before the deferred importmap.

**You do not render the tab strip, and you do not have to.** If the page sits
inside a place the host knows about, `shell_page_tabs` already shows that place's
sibling screens, with the right one lit. A page outside any place says so by
saying nothing:

```twig
{# templates/sightings/index.html.twig (your bundle) #}
{% block shell_page_tabs %}{% endblock %}
```

**Pick your rung.** A full-bleed screen — a map that reaches the edges, a split
view — extends `@UhifadhiShell/shell.html.twig` and fills `content` directly:
furniture, no `.page` wrapper, nothing to fight with negative margins. A print
view or an export extends `@UhifadhiShell/document.html.twig` and fills `body`:
no furniture at all, but still the theme, because a printed page with no tokens
is black Times New Roman on white.

To contribute a row to the sidebar, implement `NavigationSourceInterface` and
tag it `shell.nav_section` — by hand, in your bundle's own extension, since a
reusable bundle's services are not autoconfigured. Gating is yours: a row the
viewer may not have is one you do not return.

### The nav seam

The shell owns the nav's **shape** — sections, rows, the location tree, carets,
the current-row treatment, the collapsed rail. It owns none of its **content**.
Content arrives through `NavigationSourceInterface`, tagged `shell.nav_section`:

- the **host** implements one, and that is where seam data enters the shell. It
  has the areas, the viewer, the permission voters and the seam's per-area
  ledger; folding those four into "these rows, in this order" is a reading for a
  person on a page.
- a **module bundle** may implement one too, for the rare platform-wide row that
  belongs to nobody's area.

Pinned by test: sections come out in declared-position order with registration as
the tie-break; **gating is the source's job** (the shell holds no
`AuthorizationChecker` and calls `is_granted` on nothing — a withheld row is
absent, never hidden); a row with no destination renders inert rather than
disappearing; folding is a class, never an omission (a caret that folds by not
rendering has nothing to reopen); **exactly one row is current** or the shell
refuses; and the nav is read live per render, so switching a module off takes its
row with it the same day.

### The area shell

The honest split, decided rather than fudged:

| The shell owns | The shell owns *not* |
|---|---|
| that sibling screens are an underlined tab strip | Overview, Modules, Zones, Settings |
| that it sits between the page head and the body | which of them this viewer has |
| that exactly one tab is lit | which one is current |
| that an unavailable tab is **absent, not disabled** | the gating decision behind that |
| that one tab is no strip at all | |

Tabs arrive through `AreaShellSourceInterface`, which the host aliases to
`shell.area_shell_source`. Today the list is hardcoded **twice** in the host —
`dashboard/_area_tabs.html.twig` and `SidebarRuntime::tabs()` — and the two have
to be edited together, which is the tell. One source, two renderings, and a test
that they cannot disagree.

Two things this fixes rather than ports: a module page inside an area currently
loses the area's tabs entirely, because the strip lives in a partial the host
includes by hand; under the frame the strip is the *default* content of
`shell_page_tabs`, so a module page that fills only its body still shows you
where you are. And "absent, not disabled" is now a rule the value object
enforces — `AreaTab` has no url-less form, so there is nothing to grey out.

*Why not an area module?* The strip has no behaviour to own: it is markup plus a
rule about lighting, and a bundle whose entire content is one Twig partial is a
dependency, not a module. If an area module is ever created, it implements this
source; it does not take the strip.

### The theme

Twenty-three tokens, frozen the same way the blocks are, because a module's
stylesheet is written against these names: `rgb(var(--c-p1))` in a patrol card is
a promise the shell made.

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
move to a data attribute would silently unstyle every module sheet); the theme is resolved
by an inline script in `<head>` **before first paint**, not by a controller that
connects after the first frame — a visitor who chose dark must not be shown a
white page first, and today they are; `system` is a real third answer, not a
synonym for light; and any custom property the shell declares that is not on the
list must be prefixed `--_` as private, because a token a module can read is a
token a module will read.

Deliberately **not** here: the map chrome tokens (`--z-ink`, `--z-paper`,
`--z-imagery`, `--z-aoi`). They belong to `uhifadhi/map-module`, the module that
owns how a layer draws; a legend palette in the shell could not be changed
without a shell release.

**What ships here and what does not.** Tokens are values — custom properties the
browser resolves — so the bundle ships them, in `public/shell.css`, along with
plain CSS for the classes its own templates emit. It ships no utility classes:
an application's Tailwind build maps these tokens into utilities (`bg-canvas`,
`text-fog`, `border-line`) in its own `app.css`, which is where a build-time
concern belongs. That file reads `rgb(var(--c-cv))` at runtime, so it keeps
working as long as this sheet lands first — which the frame guarantees and a
test enforces. A bundle that shipped its theme any other way would only theme
hosts that had the right build.

### The module grid

**The ruling: the shell claims the picture, not the grouping, not the URL, and
not the customize screen.** The seam declined the grid and named the shell as
its claimant, so this had to be answered rather than inherited. The independent-life
test splits it:

- **the picture lives alone.** Cards in category groups, a status chip, a lens
  marker on the group a department leads, an empty state — a layout, complete
  given a list of groups, and the same layout wherever it appears (the area's
  Modules tab, a department page, a future search result). One implementation, at
  `@UhifadhiShell/_module_grid.html.twig`.
- **the grouping does not.** Which cards, in which groups, in which order, and
  which department leads one is a reading of the catalogue for a particular
  viewer on a particular area — it needs the area, the viewer and the department
  lens, none of which the shell has or should acquire. The host's
  `ModuleGridService` stays where it is.
- **the URL does not.** `/areas/{uuid}/modules` is the host's URL space, gated by
  the host's `module.view`, resolving the host's area entity. A controller here
  would drag all three across the boundary to save an include.
- **the customize screen is not the grid's neighbour but its opposite.** It is a
  form that *writes* per-area install state: a POST, a CSRF token, an
  authorization decision, a flush. The shell has no writes anywhere in it, and
  the first one would end "the layout renders from a fixture with no database".
  A test greps the templates for `method="post"` and `csrf_token`.

### Changing any of it

The sockets and the tokens are a public API. The policy is short, and it is
enforced by the frozen lists refusing to agree with anything else:

- **Adding** a socket or a token: append it to the frozen list in the test, in
  the group it belongs to, with the comment saying who fills it; bump the minor
  version. `LayoutContract::VERSION` exists so a module bundle can require a
  shell that has the sockets it fills.
- **Renaming**: major version, a deprecation cycle, and the old name kept as an
  alias block for one release. `content` is the worked example.
- **Removing**: as renaming, plus a note in this section.

Editing a frozen list to make a build pass is the failure mode the lists exist to
catch.

## Boundaries: what the shell is not

Concretely, this bundle ships **no entities, no repositories, no doctrine
dependency, no migrations, no controllers and no routes**, and
`tests/Unit/BoundaryTest` fails the build if that changes. It also ships no
`src/Domain`, `src/Application`, `src/Infrastructure` or `src/UI` — folders are
named by technical kind. `templates/` is not an exception to that rule: it is not
a domain folder, it is this bundle's entire subject.

### Why the shell does not require the seam

This is the ruling worth arguing, because the obvious defence runs the other way.

The Symfony analogy offered for a shell → seam require is `twig-bundle`
depending on `framework-bundle`: one bundle may depend on another, and nothing
about the platform's shape forbids it. That analogy is real but it does not reach.
`twig-bundle` depends on `framework-bundle` for **machinery** — the kernel, the
config pass, the container conventions. The shell would be requiring the seam to
read **domain data**.

Three consequences decide it:

1. **The data is not usable raw anyway.** A nav row needs the area, the viewer
   and the permission decision; a grid card needs the department lens. The seam
   has none of those, so *something* has to compose the answer before the shell
   can draw it — and whatever composes it can hand over a value object as easily
   as the shell could fetch a row. The require buys nothing.
2. **Entities in templates are interrogated.** The moment a seam entity is in
   scope inside a Twig file, somebody writes `{% if module.slug == 'overview' %}`,
   and the module-blindness the seam and the shell both promise is gone. A `ModuleCard` value
   object cannot be interrogated that way — there is nothing on it to switch on
   that is not also a thing every card has.
3. **A shell that requires a seam runtime cannot shell an installation that has
   no modules.** `tests/Integration/TestKernel` is the proof: framework + twig +
   this bundle, booting and rendering, with no seam under it at all.

So: **`composer.json` requires no `uhifadhi/*` package**, and a test asserts it,
because this is precisely the kind of ruling that gets quietly reversed by one
`composer require` during an extraction.

## What is here

| Piece | File |
|---|---|
| The Symfony plug, the stylesheet path, the nav tag | `src/UhifadhiShellBundle.php` |
| Config tree (`shell:`) | `src/DependencyInjection/ShellConfiguration.php` |
| Static service wiring, and the published ids | `config/services.php` |
| The frozen manifest | `src/Contract/LayoutContract.php` |
| The seams | `src/Contract/` |
| The shapes that cross them | `src/Model/` |
| The frames and partials | `templates/` |
| The token set and the shell's own CSS | `public/shell.css` |
| The four glyphs its chrome draws with | `assets/icons/shell/` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: two seam implementations and nothing else | `tests/Integration/Fixtures/HostKernel.php` |

## Installation

```bash
composer require uhifadhi/shell-module
```

Every code block below opens with a comment naming the file it belongs in. Where
a block belongs to the application rather than to a module bundle, the comment
says so.

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`Uhifadhi\Shell\UhifadhiShellBundle` to `config/bundles.php`. A host
then implements the two seams and points the shell at them:

```php
// config/services.php (your application)
$services->set(App\Shell\HostNavigation::class)->tag('shell.nav_section');
$services->alias('shell.area_shell_source', App\Shell\AreaShell::class);
```

…and its pages extend the frame:

```twig
{# templates/zones/index.html.twig (your application) #}
{% extends '@UhifadhiShell/page.html.twig' %}
{% block shell_page_title %}Zones{% endblock %}
{% block shell_page %}…{% endblock %}
```

### What a fresh installation shows

Before a host has implemented either seam — and before it has a user, a
security bundle or a single route — the shell still renders: the brand tile and
wordmark, an empty sidebar, the top bar with its theme toggle, the page frame,
and whatever the page put in `shell_page`. Nothing is a placeholder and nothing
is a stub.

This is a boundary, not a courtesy. The shell's own furniture asks for no viewer:
it never touches `app.user`, never calls `is_granted`, and generates the brand's
home link only if the configured route actually exists, falling back to `/`. The
account chrome — the user pill, sign-out, an impersonation banner — is the host's,
through `shell_topbar_actions` and `shell_banner`, because those are the sockets
whose content needs to know who is signed in. A shell that reached for a viewer
would fail on every installation that has not grown a team yet, which is every
installation on its first day.

## Configuration

```yaml
# config/packages/shell.yaml
shell:
    brand_name: Uhifadhi           # the wordmark beside the brand tile
    home_route: dashboard_index    # where the tile links
    default_theme: light           # light | dark | system
```

Every key has a default and the tree is closed, so an unknown key fails loudly
rather than being ignored. Each one is something the shell genuinely **cannot**
know: the deployment's name, the host's route names, a first-visit preference.
There is deliberately **no key listing nav entries, area tabs or modules** —
those arrive as data through the seams, because a YAML nav is a nav no permission
check ever reaches.

## Development

```bash
composer install
composer check   # cs:check -> phpstan (max) -> the whole suite
```

- PHP 8.4+, PHPStan level **max**, php-cs-fixer `@Symfony` + `@Symfony:risky`.
- **Tests first, always.** This repository is that rule taken literally: the
  whole contract was written before a line of the shell existed.
- `tests/Integration/TestKernel.php` is framework + twig + ux-icons + this
  bundle, with `strict_variables` on, and **no database** — that is the boundary,
  not a convenience.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle shells. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.
