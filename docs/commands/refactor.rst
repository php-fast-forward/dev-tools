refactor
========

Runs Rector for code refactoring.

Description
-----------

The ``refactor`` command (alias: ``rector``) runs Rector to automatically
refactor PHP code. Without ``--fix``, it runs in dry-run mode.

Usage
-----

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" refactor
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" refactor --fix
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix refactor
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" refactor [options]
   vendor/bin/dev-tools refactor [options]

Options
-------

``--fix, -f``
   Automatically fix code refactoring issues. Without this option, runs in dry-run mode.

``--config=FILE, -c FILE``
   Path to the Rector configuration file. Default: ``rector.php``. When supplied,
   a value is required; an invalid path fails instead of using the default.

``--progress``
   Enable progress output from Rector.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Run Rector in dry-run mode:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" refactor

Apply fixes automatically:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" refactor --fix

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. No refactoring needed or fixes applied.
   * - 1
     - Failure. Refactoring issues found.

Behavior
---------

- Explicit ``--config`` is resolved and passed unchanged to Rector. Without it,
  local ``rector.php`` is preferred when present.
- Packaged default includes Fast Forward custom Rector rules plus shared Rector sets.
- Progress output is disabled by default; use ``--progress`` to re-enable it in
  text mode.
- ``--json`` and ``--pretty-json`` forward JSON mode to Rector and suppress its
  progress bar.
- Uses ``--dry-run`` mode unless ``--fix`` is specified.
