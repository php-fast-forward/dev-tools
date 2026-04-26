standards
==========

Runs Fast Forward code standards checks.

Description
-----------

The ``standards`` command runs the full quality pipeline:

1. ``refactor`` - Rector code refactoring
2. ``phpdoc`` - PHPDoc checks and fixes
3. ``code-style`` - Code style checking
4. ``reports`` - Documentation and test reports

Usage
-----

.. code-block:: bash

   composer standards
   composer standards --fix
   composer dev-tools standards -- [options]
   vendor/bin/dev-tools standards [options]

Alternatively, you can run the unified fixing variant:

.. code-block:: bash

   composer dev-tools
   composer dev-tools:fix

Options
-------

This command supports:

- ``--cache-dir`` to provide a base cache directory for nested cache-aware
  phases;
- ``--cache`` to force cache-aware nested phases to keep caching enabled;
- ``--no-cache`` to force cache-aware nested phases to disable caching;
- ``--progress`` to re-enable progress output from the nested phases in text
  mode;
- ``--json`` to emit a structured machine-readable payload instead of the
  normal terminal output;
- ``--pretty-json`` to emit the same structured payload with indentation for
  terminal inspection.

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All checks passed.
   * - 1
     - Failure. One or more checks failed.

Behavior
---------

- This is the default command when running ``composer dev-tools`` without args.
- Each phase runs in sequence; if any phase fails, the command returns failure.
- The ``--fix`` option is passed to all phases that support it.
- Cache stays enabled by default for nested cache-aware phases; omit both flags
  to keep the command default, pass ``--cache`` to force it on, and pass
  ``--no-cache`` to force it off.
- The explicit cache intent is propagated to the nested ``phpdoc`` and
  ``reports`` phases. ``refactor`` and ``code-style`` do not consume this
  contract.
- When ``--cache-dir`` is provided, ``phpdoc`` and ``reports`` receive nested
  cache directories under that base path. When it is omitted, each nested tool
  keeps its own default cache directory.
- Progress output is disabled by default across nested phases; use
  ``--progress`` to re-enable it in text mode.
- ``--json`` and ``--pretty-json`` are forwarded through every phase so the
  pipeline stays machine-readable end to end.
