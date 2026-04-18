ADR 0003: Sync Preserves Consumer Customizations
================================================

Status
------

Accepted.

Context
-------

``dev-tools:sync`` distributes shared defaults into consumer repositories, but
consumer projects may already have custom workflows, Dependabot rules,
``.editorconfig`` settings, skills, or repository-specific ignore files.

Overwriting consumer-owned files by default would make adoption risky and could
silently remove project-specific behavior.

Decision
--------

Sync creates missing shared assets and repairs known managed links, while
preserving existing consumer-owned files and non-symlink directories by default.

Generated updates that must be in place, such as Composer script entries, are
applied in place instead of replacing the whole file.

Consequences
------------

- Consumer repositories can adopt DevTools incrementally.
- Maintainers must compare existing custom files with packaged defaults when
  they want newer shared behavior.
- Sync output may be conservative; a repository can intentionally diverge from
  the packaged default.
- Future overwrite features should remain explicit and reviewable.
