code-style
==========

Checks and fixes code style issues using EasyCodingStandard and Composer Normalize.

Description
-----------

The ``code-style`` command orchestrates code style checking and fixing using:

- ``composer update --lock --quiet`` - updates lock file
- ``composer normalize`` - normalizes composer.json format
- ``vendor/bin/ecs`` - EasyCodingStandard for PHP code style

Usage
-----

.. code-block:: bash

   composer code-style
   composer dev-tools code-style

   composer code-style --fix
   composer dev-tools:fix code-style

   vendor/bin/dev-tools code-style [options]

Options
-------

``--fix, -f``
   Automatically fix code style issues. Without this option, ECS runs in dry-run mode.

Examples
--------

Check code style (dry-run):

.. code-block:: bash

   composer code-style

Fix code style automatically:

.. code-block:: bash

   composer code-style --fix
   composer dev-tools:fix code-style

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

- Always runs ``composer update --lock --quiet`` first.
- Composer Normalize runs in ``--dry-run`` mode unless ``--fix`` is specified.
- ECS uses local ``ecs.php`` when present, otherwise falls back to packaged default.
- The command executes processes in sequence via ProcessQueue.
