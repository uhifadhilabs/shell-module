# Changing the contract

The policy for changing a socket or a theme token — the sockets listed in
[the block contract](blocks.md) and the tokens listed in [the theme](theming.md).

The sockets and the tokens are a public API. The policy is short, and it is
enforced by the frozen lists refusing to agree with anything else:

- **Adding** a socket or a token: append it to the frozen list in the test, in
  the group it belongs to, with the comment saying who fills it; bump the minor
  version. `LayoutContract::VERSION` exists so a module can require a
  shell that has the sockets it fills.
- **Renaming**: major version, a deprecation cycle, and the old name kept as an
  alias block for one release. `content` is the worked example.
- **Removing**: as renaming, plus a note in this document.

Editing a frozen list to make a build pass is the failure mode the lists exist to
catch.
