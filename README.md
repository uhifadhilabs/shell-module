# uhifadhi/shell-module

The **shell**: what an uhifadhi installation looks like — the document, the page
frame, the navigation seams and the theme every module's pages mount into.
A [uhifadhi](https://github.com/uhifadhilabs) platform module.

> Installs with `composer require uhifadhi/shell-module`, registers via Flex,
> and provides three page frames with twenty-three named blocks, two navigation
> seams, a twenty-three token theme in light and dark, the module grid, the
> masterbrand tab icon and the Stimulus controllers its own furniture moves by,
> plus a welcome page an application may import at `/` in one line.
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
  - [The furniture moves](#the-furniture-moves)
  - [The tab icon](#the-tab-icon)
  - [The module grid](#the-module-grid)
  - [Changing any of it](#changing-any-of-it)
- [Boundaries: what the shell is not](#boundaries-what-the-shell-is-not)
  - [The one URL, and the one line that grants it](#the-one-url-and-the-one-line-that-grants-it)
  - [Why the shell does not require the seam](#why-the-shell-does-not-require-the-seam)
- [What is here](#what-is-here)
- [Installation](#installation)
  - [What a fresh installation shows](#what-a-fresh-installation-shows)
  - [Upgrading from 0.4: one required line](#upgrading-from-04-one-required-line)
- [Configuration](#configuration)
- [Development](#development)
- [License](#license)

## The architecture

**Uhifadhi is one skeleton and a set of modules.**
[`uhifadhi/uhifadhi`](https://github.com/uhifadhilabs/uhifadhi) is the project
skeleton — copied once, never updated; everything else arrives as a module,
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
| `importmap` | the shell, by default — `importmap('app')`; a host with another entrypoint refills it |
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
compatibility rather than clarity: every host page and every module page fills
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

The whole point, in the shortest page that works. A module's page extends
the frame, fills sockets, and types no furniture:

```twig
{# templates/sightings/index.html.twig (your module) #}
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
{# templates/sightings/index.html.twig (your module) #}
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
module from whichever host template was open at the time — which is the
reason this package exists.

**Your stylesheet goes after the shell's, and `parent()` is what puts it there.**

```twig
{# templates/sightings/_base.html.twig (your module) #}
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
{# templates/sightings/index.html.twig (your module) #}
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
- a **module** may implement one too, for the rare platform-wide row that
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

### The furniture moves

**The shell ships its controls' behaviour, not only their markup.** It used to
ship only the markup: the theme toggle, the sidebar's collapse and the tree's
carets each carried a `data-action` naming a Stimulus controller the host was
expected to write. The host this bundle was extracted from had written them, so
the defect was invisible there and total everywhere else — a fresh installation got a
sun button that did nothing, a collapse button that collapsed nothing, and
carets that folded nothing. Furniture that looks operable and is not is worse
than furniture that is absent.

The reasoning behind that split — a bundle cannot contribute an importmap entry
— is true and was never the whole story. A UX package ships Stimulus controllers
the way `symfony/ux-turbo` does: an AssetMapper path plus a `symfony.controllers`
block in `assets/package.json`, which Flex enables in the application's
`assets/controllers.json` on install. Nothing is built and nothing is copied into
an application.

| Control | Controller | What it does |
|---|---|---|
| the top bar's sun | `theme` | writes the choice; the head's pre-paint script still applies it |
| the sidebar's chevrons | `sidebar` | collapses to the icon rail, and remembers |
| a tree caret | `sidebar-tree` | folds one branch; never navigates, never persisted |

Three consequences worth naming:

- **The shell requires `symfony/asset-mapper` and `symfony/stimulus-bundle`.**
  Every installation has the shell, so the shell is where the platform's asset
  pipeline is declared; a module that adds controllers later requires the same
  packages and composer resolves one copy. This is the one place the "requires no
  other ring" ruling does not reach: those are Symfony's rings, not uhifadhi's.
- **The document renders `importmap('app')`** as the default content of the
  `importmap` socket. A document whose furniture needs Stimulus and which never
  emits an importmap ships controls that cannot work.
- **The identifiers are namespaced by the package**, because StimulusBundle
  derives them from the composer name Flex keys `controllers.json` by:
  `uhifadhi--shell-module--theme`, and so on. A template that types anything else
  binds to nothing, silently.

**What is remembered is applied before the first paint.** The theme already was;
the sidebar's width now is too, through a `shell-rail` class the head's inline
script puts on `<html>` and the stylesheet draws the rail from — otherwise a
remembered rail arrives when the controller connects and a 236px sidebar visibly
jumps to 66px on every load.

### The tab icon

**The favicon is the full masterbrand mark** — the knockout U tile with the child
tile in the cut corner — shipped at `public/favicon.svg` and linked by the
document. Until it existed, every page of every installation drew a `/favicon.ico`
request and every installation answered it with a 404 in its first minute.

One SVG, at every size, in both palettes: a favicon is fetched on its own, with
no `shell.css`, no `html.dark` and no tokens, so the paint is written into the
file as the literals `--c-acc` and `--c-cv` resolve to and switched with
`prefers-color-scheme`. It is the only place in the platform where the brand
colours are restated, and a test compares its geometry with
`_brand_mark.html.twig` path by path so the two cannot drift.

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
  version. `LayoutContract::VERSION` exists so a module can require a
  shell that has the sockets it fills.
- **Renaming**: major version, a deprecation cycle, and the old name kept as an
  alias block for one release. `content` is the worked example.
- **Removing**: as renaming, plus a note in this section.

Editing a frozen list to make a build pass is the failure mode the lists exist to
catch.

## Boundaries: what the shell is not

Concretely, this bundle ships **no entities, no repositories, no doctrine
dependency and no migrations**, and `tests/Unit/BoundaryTest` fails the build if
that changes. It also ships no `src/Domain`, `src/Application`,
`src/Infrastructure` or `src/UI` — folders are named by technical kind.
`templates/` is not an exception to that rule: it is not a domain folder, it is
this bundle's entire subject.

It ships **one route and one controller**, which is not an exception either —
see below for the rule they live under.

### The one URL, and the one line that grants it

The boundary is **consent, not abstinence**: the shell claims no URL an
application has not asked it to claim.

The shell ships the welcome page's route as an importable resource — the pattern
WebProfilerBundle uses for `/_profiler` and FrameworkBundle for its error pages —
and loads it nowhere. An application accepts it in one line, in a file the
application owns:

```yaml
# config/routes/shell.yaml (your application)
shell:
    resource: '@UhifadhiShellBundle/config/routes/welcome.php'
```

That import defines the route `welcome` at `/`. Edit it to point `/` at your own
home screen, or delete it and the address is yours again — nothing is left
behind, because nothing in the shell loads that resource or depends on the route
existing. `tests/Integration/Routing/RouteResourceTest` boots the same host with
and without the line and asserts exactly that: 404 without it, the welcome page
with it.

The resource is **PHP, not YAML**, for the reason `config/services.php` is: a
reusable bundle must not force `symfony/yaml` onto the hosts that install it.

Behind that route is `Uhifadhi\Shell\Controller\WelcomeController`, and the
rule its successors live under is:

- **Presentation only.** A shell controller reads what the shell can read for
  itself — Composer's runtime metadata, the shell's own configured state — and
  renders one of the shell's own templates. It reads no entity, opens no
  connection and requires no seam; domain data reaches the shell through the
  tagged source interfaces in `src/Contract`, exactly as the sidebar's rows do.
- **Reachable only through the import.** A controller no application has
  imported a route for is a controller nothing can call.
- **No base class.** It does not extend `AbstractController`; it takes its
  dependencies in its constructor and is wired explicitly in
  `config/services.php`, like every other service here.

A second `Uhifadhi\Shell\Controller\*` under those terms is ordinary work, not
a change to this ruling. The area URL space, its permission gates and its entity
resolution stay the host's: `/areas/{uuid}/modules` is the host's, gated by the
host's permissions and resolving the host's area entity — a controller here would
drag all three across the boundary to save one template include.

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
| The welcome page a fresh installation serves at `/` | `templates/welcome.html.twig` |
| The controller that renders it | `src/Controller/WelcomeController.php` |
| The route resource an application imports to reach it | `config/routes/welcome.php` |
| What this installation is made of, read from composer | `src/Service/Installation.php` |
| The token set and the shell's own CSS | `public/shell.css` |
| The tab icon: the masterbrand mark, both palettes in one SVG | `public/favicon.svg` |
| The furniture's behaviour, as UX-packaged Stimulus controllers | `assets/controllers/` |
| What Flex reads to enable them in a host | `assets/package.json` |
| The four glyphs its chrome draws with | `assets/icons/shell/` |
| Test host app | `tests/Integration/TestKernel.php` |
| A host, minimally: two seam implementations and nothing else | `tests/Integration/Fixtures/HostKernel.php` |

## Installation

```bash
composer require uhifadhi/shell-module
```

Every code block below opens with a comment naming the file it belongs in. Where
a block belongs to the application rather than to a module, the comment
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

**And it has one page of its own**, `@UhifadhiShell/welcome.html.twig` — the
screen an installation serves at `/` before it has grown a home screen. Until it
existed, an installation of the seam and the shell answered `/` with Symfony's
welcome-404: a correct installation looking like a broken one, on its first
minute. The page lists what is installed, says why the sidebar beside it is
empty, and says that `composer require uhifadhi/<name>-module` is what fills it.

**Who can read it is not the shell's question.** On an installation with no
firewall — the skeleton, the seam and the shell and nothing else — the page is
open because nothing is closed. On one that has installed identity
(`uhifadhi/team-module`), whose documented `security.yaml` is default-closed, the
same page is a signed-in view and a stranger asking for `/` is sent to `/login`
instead. Neither is something this bundle does or could do: the shell renders
what it is asked for, and `access_control` is the application's file.

**The list is read from composer at request time** — `Composer\InstalledVersions`,
through `src/Service/Installation.php` — because a page whose whole job is to
report on an installation cannot report a list somebody typed: the first module
anybody installs makes it wrong, and installing one is the instruction this same
page gives. Two rows carry a line of description, the shell's own and the seam's,
and no others do: a shell that described a module would be a shell that knew what
modules are.

`WelcomeController` reads that list and hands it to the template as an ordinary
variable. There is no `shell_packages()` Twig function: one page's data has no
business being in scope on every page in the platform.

**The route is the shell's; the address is yours.** The shell ships the route as
a resource and loads it nowhere. The page is reachable because the application
imports it, in one line, in `config/routes/shell.yaml` — which the project
skeleton ships:

```yaml
# config/routes/shell.yaml (your application)
shell:
    resource: '@UhifadhiShellBundle/config/routes/welcome.php'
```

That defines the route `welcome` at `/`. Point `/` at your own home screen by
editing that file, or delete the import, the day the installation has a real home
screen. Nothing in the shell depends on the route name or on the route existing.
See [the boundary](#the-one-url-and-the-one-line-that-grants-it) for why the
mechanism is an import rather than something the bundle does for you.

The rest is a boundary, not a courtesy. The shell's own furniture asks for no viewer:
it never touches `app.user`, never calls `is_granted`, and generates the brand's
home link only if the configured route actually exists, falling back to `/`. The
account chrome — the user pill, sign-out, an impersonation banner — is the host's,
through `shell_topbar_actions` and `shell_banner`, because those are the sockets
whose content needs to know who is signed in. A shell that reached for a viewer
would fail on every installation that has not grown a team yet, which is every
installation on its first day.

### Upgrading from 0.4: one required line

**An installation on 0.4 must edit `config/routes/shell.yaml` when it takes 0.5.**
This is the one step, and it is not optional: leaving the old file in place gives
a 500 on `/`.

On 0.4 the route was DEFINED in the application, pointing Symfony's
`TemplateController` at the shell's template:

```yaml
# config/routes/shell.yaml — the 0.4 form. Replace it.
welcome:
    path: /
    controller: Symfony\Bundle\FrameworkBundle\Controller\TemplateController
    defaults:
        template: '@UhifadhiShell/welcome.html.twig'
```

On 0.5 the welcome page has a controller of its own, because it has real work to
do — it reads what is installed, live from Composer, and hands the list to the
template. `TemplateController` renders the template with no variables, so the old
route now serves a template whose data never arrives. Replace the file's contents
with the import:

```yaml
# config/routes/shell.yaml — the 0.5 form.
shell:
    resource: '@UhifadhiShellBundle/config/routes/welcome.php'
```

Nothing else changes: the route is still named `welcome`, still at `/`, still
yours to repoint or delete. An installation that had already replaced this file
with a home screen of its own has nothing to do — it never had the welcome route
and does not want it.

Recipe `uhifadhi/shell-module` 0.5 writes the new form for a fresh installation.
`composer recipes:update uhifadhi/shell-module` will offer it to an existing one,
but it arrives as a git conflict rather than a clean replacement — the old file
was written by an older recipe, or by hand — so the two-line edit above is the
shorter road.

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
