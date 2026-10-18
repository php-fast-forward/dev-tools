changelog
=========

DevTools delegates changelog behavior to the independent
`Fast Forward Changelog <https://github.com/php-fast-forward/changelog/tree/v0.2.0>`_
package. It does not implement parsing, version inference, history maintenance
or publication policy itself.

Runtime and migration
---------------------

``vendor/bin/dev-tools changelog <arguments...>`` invokes
``cpx --skip-local fast-forward/changelog:0.2.0`` from the consumer project.
Run DevTools itself with PHP 8.5 for this command, and make CPX available on
``PATH`` with the same compatible runtime. Other DevTools commands still support
PHP 8.3. This isolated tool installation
does not raise the consumer package's PHP 8.3 requirement or add Changelog to
its Composer dependency graph. Consult the
`official CPX setup <https://github.com/laravel/cpx>`_ before installation.

The five internal ``changelog:*`` commands have been removed. Their options
and JSON envelopes are not aliases for the external CLI. Inspect the selected
runtime before adapting scripts:

.. code-block:: bash

   vendor/bin/dev-tools changelog list --raw
   vendor/bin/dev-tools changelog help add
   vendor/bin/dev-tools changelog help check
   vendor/bin/dev-tools changelog help version

Use ``vendor/bin/dev-tools changelog -- <command> [options]`` to forward all
external options without wrapper interpretation, including the external
``--help`` option. The wrapper streams the library's stdout/stderr and preserves
its exit status instead of applying the DevTools JSON envelope.

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

   vendor/bin/dev-tools changelog add "Preserves Markdown links when exporting release notes." --category=fixed --no-interaction
   vendor/bin/dev-tools changelog check --no-interaction

``add`` creates the fragment directory when necessary and leaves central history
unchanged. Categories describe the nature of the change; ``type`` separately
records its semantic-version impact. Preserve other contributions' fragments.
After committing the contribution, validate it against the intended Git base:

.. code-block:: bash

   vendor/bin/dev-tools changelog check --since=origin/main --no-interaction

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

   vendor/bin/dev-tools changelog status --source=tags --no-interaction
   vendor/bin/dev-tools changelog format --dry-run --no-interaction
   vendor/bin/dev-tools changelog backfill --dry-run --source=tags --no-interaction
   vendor/bin/dev-tools changelog notes <version> --no-interaction

Read ``help <command>`` before selecting write options. Test ``version`` and
other history-writing operations only in a disposable checkout: consolidation
writes ``CHANGELOG.md`` and deletes consumed fragments. Use the canonical GitHub
origin when repository discovery and comparison links depend on it. Review
introductions, published dates, descriptions, existing link bytes, footer order
and the single new version link after every rehearsal.

The repository caller selects the external workflow's ``@v0`` channel, which
tracks published 0.x releases. Local CLI execution is fixed to 0.2.0; resolve
the channel and review compatibility when updating either integration.
The caller preserves ``release/vnext`` and
``Prepare release v{next_version}``. The Bot owns that branch: let automation
regenerate its PR and inspect the new exact head rather than editing the branch
or forging ownership trailers.

Adoption keeps ``publish: false``. Preparing or merging a version PR does not
authorize publication. Historical GitHub Release reconciliation also remains
separate: approve and integrate canonical history first, then apply authorized
body changes or create releases only for verified existing tags. Preserve tag
targets, assets and draft/prerelease flags, and reread each changed Release.
The external ``notes`` command extracts the selected version body; verify any
reference-style links also resolve when that body is rendered independently.

See :doc:`../usage/github-actions` and
:doc:`../internals/release-publishing` for automation and approval boundaries.
