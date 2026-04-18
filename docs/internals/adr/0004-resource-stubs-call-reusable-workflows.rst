ADR 0004: Resource Stubs Call Reusable Workflows
================================================

Status
------

Accepted.

Context
-------

Fast Forward maintains shared GitHub Actions behavior for many consumer
repositories. Copying full workflow implementations into every repository would
make updates slow, noisy, and inconsistent.

Consumer repositories still need local workflow files so GitHub can expose
standard Actions entry points and repository-specific triggers.

Decision
--------

Consumer repositories receive small workflow stubs from
``resources/github-actions``. Those stubs call reusable workflows implemented in
the DevTools repository through ``workflow_call``.

The reusable workflow owns the shared behavior, while the stub keeps the
consumer repository integration point small and predictable.

Consequences
------------

- Central workflow fixes can benefit consumers without large per-repository
  edits.
- Consumer stubs must remain compatible with the reusable workflow interface.
- Changes to reusable workflow inputs or secrets are architectural changes for
  downstream repositories.
- Documentation and sync behavior should describe both the local stub and the
  central workflow role.
