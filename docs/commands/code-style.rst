code-style
==========

Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.

Description
-----------

The ``code-style`` command orchestrates code style checking and fixing using:

- ``composer normalize`` - runs the installed plugin in the isolated runtime
  root against the consumer's absolute manifest path with ``--no-update-lock``
- ``vendor/bin/ecs`` - EasyCodingStandard for PHP code style

Usage
-----

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style --fix
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix code-style

   vendor/bin/dev-tools code-style [options]

Options
-------

``--fix, -f``
   Automatically fix code style issues. Without this option, ECS runs in dry-run mode.

``--progress``
   Enable progress output from ECS.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Check code style (dry-run):

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style

Fix code style automatically:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style --fix
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix code-style

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All checks passed or fixes applied.
   * - 1
     - Failure. Style issues found or fix failed.

Behavior
---------

- Does not run Composer dependency updates or refresh the lockfile first.
- Composer Normalize runs in ``--dry-run`` mode unless ``--fix`` is specified.
- ECS uses local ``ecs.php`` when present, otherwise falls back to packaged default.
- Progress output is disabled by default; use ``--progress`` to re-enable it in
  text mode.
- ``--json`` and ``--pretty-json`` forward JSON mode to ECS and suppress its
  progress bar so the structured payload stays machine-readable.
- The command executes processes in sequence via ProcessQueue.
