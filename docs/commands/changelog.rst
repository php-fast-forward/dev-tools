External Fast Forward Changelog
===============================

Use the independent
`Fast Forward Changelog <https://github.com/php-fast-forward/changelog/tree/v0.3.0>`_
package for fragments, version planning, history and publication policy.

Runtime
-------

Run ``cpx --skip-local fast-forward/changelog:0.3.0`` from the consumer project.
Make CPX available on ``PATH`` with PHP 8.5. This isolated tool installation
does not raise the consumer package's PHP 8.3 requirement or add Changelog to
its Composer dependency graph. Consult the
`official CPX setup <https://github.com/laravel/cpx>`_ before installation.

Inspect the selected runtime before running its commands:

.. code-block:: bash

   cpx --skip-local fast-forward/changelog:0.3.0 list --raw
   cpx --skip-local fast-forward/changelog:0.3.0 help add
   cpx --skip-local fast-forward/changelog:0.3.0 help check
   cpx --skip-local fast-forward/changelog:0.3.0 help version

Use ``add`` for authoring, ``check`` for contribution validation, ``status`` for
pending/version planning, ``version`` for consolidation, and ``notes`` for
release-note extraction. The external package owns these command contracts.

Contribution fragments
----------------------

Each observable change adds one independent ``.changelog/*.md`` file. For example:

.. code-block:: markdown

   ---
   category: fixed
   type: patch
   ---

   Preserves Markdown links when exporting release notes.

Or create it once through the CLI:

.. code-block:: bash

   cpx --skip-local fast-forward/changelog:0.3.0 add "Preserves Markdown links when exporting release notes." --category=fixed --no-interaction
   cpx --skip-local fast-forward/changelog:0.3.0 check --no-interaction

``add`` creates the fragment directory when necessary and leaves central history
unchanged. Categories describe the nature of the change; ``type`` separately
records its semantic-version impact. Preserve other contributions' fragments.
After committing the contribution, validate it against the intended Git base:

.. code-block:: bash

   cpx --skip-local fast-forward/changelog:0.3.0 check --since=origin/main --no-interaction

No new ``Unreleased`` section is used. PR metadata may remain absent before a
PR exists. Automation enables ``resolve-pull-requests: true`` consistently for
planning, preparation, ownership checks and publication. It proves fragment
origins from committed bytes and merged PR evidence without rewriting fragment
metadata; no follow-up fragment edit is required after opening a PR. Unknown
origins receive no invented PR, author or commit reference. Local previews
require the explicit ``--resolve-pull-requests`` option for equivalent lookup.

History and release preparation
-------------------------------

.. code-block:: bash

   cpx --skip-local fast-forward/changelog:0.3.0 status --source=tags --no-interaction
   cpx --skip-local fast-forward/changelog:0.3.0 format --dry-run --no-interaction
   cpx --skip-local fast-forward/changelog:0.3.0 backfill --dry-run --source=tags --no-interaction
   cpx --skip-local fast-forward/changelog:0.3.0 notes <version> --no-interaction

Read ``help <command>`` before selecting write options. Test ``version`` and
other history-writing operations only in a disposable checkout: consolidation
writes ``CHANGELOG.md`` and deletes consumed fragments. Use the canonical GitHub
origin when repository discovery and comparison links depend on it. Review
introductions, published dates, descriptions, existing link bytes, footer order
and the single new version link after every rehearsal.

The repository caller selects the external workflow's ``@v0`` channel, which
tracks published 0.x releases. Local CLI execution is fixed to 0.3.0; resolve
the channel and review compatibility when updating either integration.
The official caller uses ``changelog/version`` and the title
``chore: update changelog``. The Bot owns that branch: let automation
regenerate its PR and inspect the new exact head rather than editing the branch
or forging ownership trailers.

The caller sets ``publish: true`` under the owner's explicit approval. Merging
the reviewed managed version PR automatically publishes its tag and GitHub
Release at the exact approved commit; the workflow never merges the PR itself.
Direct manual publication requires separate authorization.
The external ``notes`` command extracts the selected
version body; verify any
reference-style links also resolve when that body is rendered independently.

See :doc:`../usage/github-actions` and
:doc:`../internals/release-publishing` for automation and approval boundaries.
