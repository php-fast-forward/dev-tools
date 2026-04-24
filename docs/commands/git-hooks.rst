git-hooks
=========

Installs packaged Fast Forward Git hooks.

Description
-----------

The ``git-hooks`` command installs the hook templates maintained in
``resources/git-hooks`` into the repository. It:

1. Copies hook files from a source directory to the target hooks directory
2. Sets executable permissions on copied hooks
3. Replaces drifted hooks defensively by removing the previous target before
   recopying it

Usage
-----

.. code-block:: bash

   composer git-hooks
   composer git-hooks [options]
   composer dev-tools git-hooks -- [options]
   vendor/bin/dev-tools git-hooks [options]

Options
-------

``--source, -s`` (optional)
   Path to the packaged Git hooks directory. Default: ``resources/git-hooks``.

``--target, -t`` (optional)
   Path to the target Git hooks directory. Default: ``.git/hooks``.

``--no-overwrite``
   Do not overwrite existing hook files.

``--dry-run``
   Preview managed Git hook drift without copying files.

``--check``
   Exit with code ``1`` when packaged Git hooks differ from the installed
   versions.

``--interactive``
   Prompt before replacing a drifted Git hook.

When a hook still cannot be rewritten because the target remains locked or
unwritable, the command logs a clear error for that hook, continues processing
the remaining hooks, and exits non-zero so ``dev-tools:sync`` reports the hook
install problem clearly instead of aborting mid-copy.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Install hooks with defaults:

.. code-block:: bash

   composer git-hooks

Install hooks without overwriting existing ones:

.. code-block:: bash

   composer git-hooks --no-overwrite

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Hooks installed successfully.
   * - 1
     - Failure. Drift detected in ``--check`` mode or one or more hooks could
       not be rewritten automatically.
