git-hooks
=========

Installs Fast Forward Git hooks and initializes GrumPHP.

.. versionadded:: 1.6
   This command was introduced in version 1.6.

Description
-----------

The ``git-hooks`` command installs Git hooks into the repository and optionally
runs GrumPHP initialization. It:

1. Runs ``grumphp git:init`` to register hooks with GrumPHP (unless ``--skip-grumphp-init``)
2. Copies hook files from a source directory to the target hooks directory
3. Sets executable permissions on copied hooks

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

``--skip-grumphp-init``
   Skip running ``grumphp git:init`` before copying hooks.

``--no-overwrite``
   Do not overwrite existing hook files.

Examples
--------

Install hooks with defaults:

.. code-block:: bash

   composer git-hooks

Install hooks without running GrumPHP init:

.. code-block:: bash

   composer git-hooks --skip-grumphp-init

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
     - Failure. GrumPHP init failed or copy error.
