agents
======

Synchronizes Fast Forward project agents into ``.agents/agents`` directory.

Description
-----------

The ``agents`` command synchronizes packaged project-agent prompts into the
consumer repository's ``.agents/agents`` directory using symlinks.

Usage
-----

.. code-block:: bash

   composer agents
   vendor/bin/dev-tools agents

Options
-------

This command does not accept additional options.

Examples
--------

Synchronize agents:

.. code-block:: bash

   composer agents

Exit Codes
----------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Agents synchronized.
   * - 1
     - Failure. No packaged agents or sync error.

Behavior
--------

- Verifies the packaged ``.agents/agents`` directory before doing any work.
- Creates the consumer ``.agents/agents`` directory when missing.
- Creates missing symlinks to packaged project agents.
- Repairs broken symlinks.
- Preserves an existing non-symlink directory instead of overwriting it.
- Reuses the same generic packaged-directory synchronizer as ``skills`` so
  both commands follow identical safety rules.
