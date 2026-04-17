skills
======

Synchronizes Fast Forward skills into .agents/skills directory.

Description
-----------

The ``skills`` command synchronizes packaged agent skills into the consumer
repository's ``.agents/skills`` directory using symlinks.

Usage
-----

.. code-block:: bash

   composer skills
   vendor/bin/dev-tools skills

Options
-------

This command does not accept additional options.

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
- Creates missing symlinks to packaged skills.
- Repairs broken symlinks.
- Preserves an existing non-symlink directory instead of overwriting it.
