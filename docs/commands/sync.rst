dev-tools:sync
============

Installs and synchronizes dev-tools scripts, GitHub Actions, .editorconfig, and more.

Description
-----------

The ``dev-tools:sync`` command synchronizes consumer-facing automation and defaults:

1. ``update-composer-json`` - adds dev-tools scripts to composer.json
2. ``copy-resource`` - copies GitHub Actions workflows, including changelog automation
3. ``copy-resource`` - copies .editorconfig
4. ``copy-resource`` - copies dependabot.yml
5. ``funding`` - synchronizes ``composer.json`` funding metadata with ``.github/FUNDING.yml``
6. ``wiki --init`` - initializes wiki as submodule
7. ``gitignore`` - merges .gitignore files
8. ``gitattributes`` - manages export-ignore rules
9. ``skills`` - synchronizes packaged skills
10. ``license`` - generates LICENSE file
11. ``git-hooks`` - installs Git hooks

Usage
-----

.. code-block:: bash

   composer dev-tools:sync
   composer dev-tools:sync [options]
   vendor/bin/dev-tools dev-tools:sync [options]

Options
-------

``--overwrite, -o``
   Overwrite existing target files. Text resources copied through
   ``copy-resource`` show a readable diff in the sync output before they are
   replaced.

``--dry-run``
   Preview managed-file drift without writing changes.

``--check``
   Report managed-file drift and exit with code ``1`` when updates are
   required.

``--interactive``
   Prompt before replacing drifted managed files.

Examples
--------

Run sync:

.. code-block:: bash

   composer dev-tools:sync

Sync with overwrite:

.. code-block:: bash

   composer dev-tools:sync --overwrite

Preview managed-file drift:

.. code-block:: bash

   composer dev-tools:sync --dry-run

Fail in CI when managed files drift:

.. code-block:: bash

   composer dev-tools:sync --check

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All syncs completed.
   * - 1
     - Failure. One or more syncs failed.

Behavior
---------

- Updates ``composer.json`` scripts, extra configuration, and managed funding
  metadata.
- Copies missing workflow stubs, including tests, reports, wiki, and changelog
  automation, plus ``.editorconfig`` and ``dependabot.yml``.
- Synchronizes supported funding metadata between ``composer.json`` and
  ``.github/FUNDING.yml``.
- When ``--overwrite`` is enabled, replaced text resources emit a unified diff
  so terminal sessions and CI logs show what changed.
- ``--dry-run`` and ``--check`` verify managed-file drift for ``composer.json``,
  funding metadata, copied resources, ``.gitignore``, ``.gitattributes``,
  ``LICENSE``, and Git hooks.
- ``--interactive`` prompts before replacing drifted managed files when the
  command is running in an interactive terminal.
- Creates ``.github/wiki`` as a git submodule when missing.
- Calls other commands in sequence.
- ``wiki`` and ``skills`` are skipped in preview/check modes until they expose a
  non-destructive verification path.
