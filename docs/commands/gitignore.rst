gitignore
=========

Merges and synchronizes .gitignore files.

Description
-----------

The ``gitignore`` command merges the canonical .gitignore from dev-tools
with the project's existing .gitignore.

Usage
-----

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" gitignore
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" gitignore [options]
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" gitignore [options]
   vendor/bin/dev-tools gitignore [options]

Options
-------

``--source, -s`` (optional)
   Path to the source .gitignore file (canonical). Default: packaged .gitignore.

``--target, -t`` (optional)
   Path to the target .gitignore file (project). Default: project root .gitignore.

``--dry-run``
   Preview managed ``.gitignore`` drift without writing the file.

``--check``
   Exit with code ``1`` when ``.gitignore`` needs an update.

``--interactive``
   Prompt before updating ``.gitignore``.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Merge .gitignore files:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" gitignore

Specify custom paths:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" gitignore --source=/path/to/source/.gitignore --target=/path/to/target/.gitignore

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. .gitignore merged successfully.
   * - 1
     - Failure. Read or write error.

Behavior
---------

- Reads the canonical .gitignore from dev-tools and merges with the project's.
- By default, the source is the packaged .gitignore and the target is the project's root.
- Canonical defaults precede consumer rules, so the last matching consumer rule
  keeps Git precedence. Existing order, repetitions and pattern whitespace are
  preserved; patterns are not sorted or globally deduplicated.
- An already present canonical prefix is not prepended again, making repeated
  synchronization idempotent. Changed defaults are added before existing rules;
  synchronization does not silently remove historical consumer rules.
- Uses the Reader, Merger, and Writer components from the GitIgnore namespace.
- ``--dry-run`` and ``--check`` render a diff against the normalized managed
  result before deciding whether to write.
