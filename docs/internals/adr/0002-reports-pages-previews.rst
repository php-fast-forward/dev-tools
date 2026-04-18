ADR 0002: Reports Pages Previews
================================

Status
------

Accepted.

Context
-------

Reports include generated documentation, coverage, and quality artifacts. Those
outputs are useful during review, but the canonical GitHub Pages site should
represent the accepted ``main`` branch.

Multiple pull requests can be open at the same time, so previews must not
overwrite each other or replace the published site.

Decision
--------

Pull request reports are published under PR-specific Pages paths such as
``previews/pr-<number>/``. Main branch reports are published to the root of the
Pages site.

Pull request comments may link to the preview, and workflow artifacts remain the
fallback when Pages links or comments are unavailable.

Consequences
------------

- Multiple pull requests can have independent report previews.
- The root Pages site remains tied to ``main``.
- Closed pull requests need preview cleanup to avoid stale artifacts.
- Maintainers should treat preview links as review aids, not release output.
