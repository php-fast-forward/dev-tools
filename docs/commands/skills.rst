skills
======

Synchronizes Fast Forward skills into .agents/skills directory.

Description
-----------

The ``skills`` command synchronizes packaged agent skills into the consumer
repository's ``.agents/skills`` directory using repository-relative symlinks.

Usage
-----

.. code-block:: bash

   composer skills
   vendor/bin/dev-tools skills

Options
-------

This command supports:

- ``--json`` to emit a structured machine-readable payload instead of the
  normal terminal output;
- ``--pretty-json`` to emit the same structured payload with indentation for
  terminal inspection.

Examples
--------

Synchronize skills:

.. code-block:: bash

   composer skills

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Skills synchronized.
   * - 1
     - Failure. No packaged skills or sync error.

Behavior
---------

- Verifies the packaged ``.agents/skills`` directory before doing any work.
- Creates the consumer ``.agents/skills`` directory when missing.
- Creates missing repository-relative symlinks to packaged skills.
- Repairs broken symlinks.
- Preserves an existing non-symlink directory instead of overwriting it.
