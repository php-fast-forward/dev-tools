dependencies
=============

Analyzes missing and unused Composer dependencies.

Description
-----------

The ``dependencies`` command (alias: ``deps``) analyzes missing and unused
Composer dependencies using two tools:

- ``composer-unused`` - detects unused packages
- ``composer-dependency-analyser`` - detects missing packages

This command ships as a direct dependency of ``fast-forward/dev-tools``.

Usage
-----

.. code-block:: bash

   composer dependencies
   composer dev-tools dependencies

   composer deps
   composer dev-tools deps

   vendor/bin/dev-tools dependencies
   vendor/bin/dev-tools deps

Options
-------

This command does not accept additional options.

Examples
--------

Run dependency analysis:

.. code-block:: bash

   composer dependencies

Using the alias:

.. code-block:: bash

   composer deps

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. No missing or unused dependencies.
   * - 1
     - Failure. Missing or unused dependencies found.

Behavior
---------

- Runs both ``composer-unused`` and ``composer-dependency-analyser``.
- ``composer-dependency-analyser`` is configured with:
  - ``--ignore-unused-deps`` (leaves unused detection to ``composer-unused``)
  - ``--ignore-prod-only-in-dev-deps`` (ignores dev-only usage in production code)
- Returns a non-zero exit code when missing or unused dependencies are found.
- Both tools must be available in ``vendor/bin/``.
