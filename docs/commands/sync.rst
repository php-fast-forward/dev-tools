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
5. ``codeowners`` - generates ``.github/CODEOWNERS`` from local metadata
6. ``funding`` - synchronizes ``composer.json`` funding metadata with ``.github/FUNDING.yml``
7. ``wiki --init`` - initializes wiki as submodule
8. ``gitignore`` - merges .gitignore files
9. ``gitattributes`` - manages export-ignore rules
10. ``skills`` - synchronizes packaged skills
11. ``agents`` - synchronizes packaged project agents
12. ``license`` - generates LICENSE file
13. ``git-hooks`` - installs Git hooks

Usage
-----

.. code-block:: bash

   composer dev-tools:sync
   composer dev-tools:sync [options]

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

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

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

- Updates ``composer.json`` scripts, extra configuration, managed funding
  metadata, and managed ``.github/CODEOWNERS`` content.
- Copies missing workflow stubs, including tests, reports, wiki, and changelog
  automation, plus ``.editorconfig`` and ``dependabot.yml``.
- Generates ``.github/CODEOWNERS`` from local metadata and preserves existing
  ownership rules by default unless ``--overwrite`` or an explicit preview
  mode is used.
- Synchronizes supported funding metadata between ``composer.json`` and
  ``.github/FUNDING.yml``.
- When ``--overwrite`` is enabled, replaced text resources emit a unified diff
  so terminal sessions and CI logs show what changed.
- ``--dry-run`` and ``--check`` verify managed-file drift for ``composer.json``,
  ``.github/CODEOWNERS``, funding metadata, copied resources, ``.gitignore``,
  ``.gitattributes``, ``LICENSE``, and Git hooks.
- ``--interactive`` prompts before replacing drifted managed files when the
  command is running in an interactive terminal.
- Creates ``.github/wiki`` as a git submodule when missing.
- Calls other commands in sequence.
- Runs ``skills`` and ``agents`` in normal mode so consumer repositories
  receive both packaged procedural skills and packaged role prompts.
- ``wiki``, ``skills``, and ``agents`` are skipped in preview/check modes
  until they expose a non-destructive verification path.
