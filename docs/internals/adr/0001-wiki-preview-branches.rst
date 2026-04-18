ADR 0001: Wiki Preview Branches
===============================

Status
------

Accepted.

Context
-------

Fast Forward repositories generate wiki documentation from source code. The
GitHub Wiki repository uses its own ``master`` branch, while the source
repository protects ``main`` and expects all source changes to pass through pull
requests.

Publishing pull request wiki output directly to ``master`` would expose
unmerged documentation. Committing generated submodule pointers directly to
``main`` would also conflict with protected branch rules.

Decision
--------

Pull request wiki output is published to a PR-scoped wiki branch named
``pr-<number>``. The parent pull request branch receives the matching
``.github/wiki`` submodule pointer update.

After the pull request is merged into ``main``, the accepted preview content is
promoted to the wiki ``master`` branch.

Consequences
------------

- Reviewers can inspect generated wiki output before merge.
- Protected ``main`` remains the only path to final wiki publication.
- Bot commits are expected on pull request branches when generated wiki pointers
  change.
- Maintainers may need to resolve ``.github/wiki`` pointer conflicts when a pull
  request is rebased after a newer preview is generated.
