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

## What it is

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

Block names, seam interfaces and theme tokens are a versioned, test-enforced
API, not a convention — see [the architecture](docs/architecture.md).

## Installation

```bash
composer require uhifadhi/shell-module
```

The bundle registers via Flex (`"type": "symfony-bundle"`), which adds
`Uhifadhi\Shell\UhifadhiShellBundle` to `config/bundles.php`.

## Getting started

Every code block below opens with a comment naming the file it belongs in. Where
a block belongs to the application rather than to a module, the comment
says so.

A host implements the two seams and points the shell at them:

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

To serve the shell's own welcome page at `/` — the screen an installation shows
before it has grown a home screen — import the route the bundle ships, in one
line, in a file the application owns:

```yaml
# config/routes/shell.yaml (your application)
shell:
    resource: '@UhifadhiShellBundle/config/routes/welcome.php'
```

Configuration, all of it optional:

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

## Learn more

- [The architecture](docs/architecture.md) — where the shell sits in the
  platform, what it guarantees, what is in this repository, and how to work on it.
- [The named sockets](docs/blocks.md) — all twenty-three blocks, who fills each,
  and how a module page fills them.
- [The nav seam and the area shell](docs/navigation.md) — how rows reach the
  sidebar and tabs reach a page.
- [The theme](docs/theming.md) — the token set, the Stimulus controllers the
  furniture moves by, and the tab icon.
- [The component vocabulary](docs/components.md) — the classes a module writes on
  its own elements: the plate, the card's tab, the KPI, the register table, the
  pager and the person's mark.
- [Boundaries](docs/boundaries.md) — what the shell is not: the one URL it ships,
  why it requires no seam, and how much of the module grid it claims.
- [Changing the contract](docs/changing-the-contract.md) — the policy for adding,
  renaming or removing a socket or a token.
- [The welcome page](docs/welcome-page.md) — what a fresh installation shows, and
  the one line an installation on 0.4 must edit for 0.5.

## License

**AGPL-3.0-or-later** — see [LICENSE](LICENSE): the same license as the uhifadhi
host this bundle shells. Use, modify and self-host freely; if you offer a
modified version to users over a network, they are entitled to the source of
what they're running.
