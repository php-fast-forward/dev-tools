standards
==========

Runs Fast Forward code standards checks.

Description
-----------

The ``standards`` command runs the full quality pipeline:

1. ``refactor`` - Rector code refactoring
2. ``phpdoc`` - PHPDoc checks and fixes
3. ``code-style`` - Code style checking
4. ``reports`` - Documentation and test reports

Usage
-----

.. code-block:: bash

   composer standards
   composer standards --fix
   composer dev-tools standards -- [options]
   vendor/bin/dev-tools standards [options]

Alternatively, you can run the unified fixing variant:

.. code-block:: bash

   composer dev-tools
   composer dev-tools:fix

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All checks passed.
   * - 1
     - Failure. One or more checks failed.

Behavior
---------

- This is the default command when running ``composer dev-tools`` without args.
- Each phase runs in sequence; if any phase fails, the command returns failure.
- The ``--fix`` option is passed to all phases that support it.
