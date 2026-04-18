git-hooks
=========

Installs packaged Fast Forward Git hooks.

Description
-----------

The ``git-hooks`` command installs the hook templates maintained in
``resources/git-hooks`` into the repository. It:

1. Copies hook files from a source directory to the target hooks directory
2. Sets executable permissions on copied hooks

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
     - Failure. Copy error.
