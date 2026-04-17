dev-tools:sync
============

Installs and synchronizes dev-tools scripts, GitHub Actions, .editorconfig, and more.

Description
-----------

The ``dev-tools:sync`` command synchronizes consumer-facing automation and defaults:

1. ``update-composer-json`` - adds dev-tools scripts to composer.json
2. ``copy-resource`` - copies GitHub Actions workflows
3. ``copy-resource`` - copies .editorconfig
4. ``copy-resource`` - copies dependabot.yml
5. ``wiki --init`` - initializes wiki as submodule
6. ``gitignore`` - merges .gitignore files
7. ``gitattributes`` - manages export-ignore rules
8. ``skills`` - synchronizes packaged skills
9. ``license`` - generates LICENSE file
10. ``git-hooks`` - installs Git hooks

Usage
-----

.. code-block:: bash

   composer dev-tools:sync
   composer dev-tools:sync [options]
   vendor/bin/dev-tools dev-tools:sync [options]

Options
-------

``--overwrite, -o``
   Overwrite existing target files.

Examples
--------

Run sync:

.. code-block:: bash

   composer dev-tools:sync

Sync with overwrite:

.. code-block:: bash

   composer dev-tools:sync --overwrite

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

- Updates ``composer.json`` scripts and extra configuration.
- Copies missing workflow stubs, ``.editorconfig``, and ``dependabot.yml``.
- Creates ``.github/wiki`` as a git submodule when missing.
- Calls other commands in sequence.
