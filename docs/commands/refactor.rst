refactor
========

Runs Rector for code refactoring.

.. versionadded:: 1.0
   Original command.

.. versionchanged:: 1.6
   Refactored to use ProcessBuilder and ProcessQueue.

Description
-----------

The ``refactor`` command (alias: ``rector``) runs Rector to automatically
refactor PHP code. Without ``--fix``, it runs in dry-run mode.

Usage
-----

.. code-block:: bash

   composer refactor
   composer refactor --fix
   composer dev-tools:fix refactor
   composer dev-tools refactor -- [options]
   vendor/bin/dev-tools refactor [options]

Options
-------

``--fix, -f``
   Automatically fix code refactoring issues. Without this option, runs in dry-run mode.

``--config, -c`` (optional)
   Path to the Rector configuration file. Default: ``rector.php``.

Examples
--------

Run Rector in dry-run mode:

.. code-block:: bash

   composer refactor

Apply fixes automatically:

.. code-block:: bash

   composer refactor --fix

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

- Local ``rector.php`` is preferred when present.
- Packaged default includes Fast Forward custom Rector rules plus shared Rector sets.
- Uses ``--dry-run`` mode unless ``--fix`` is specified.
