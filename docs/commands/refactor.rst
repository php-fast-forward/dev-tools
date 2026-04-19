refactor
========

Runs Rector for code refactoring.

Description
-----------

The ``refactor`` command (alias: ``rector``) runs Rector to automatically
refactor PHP code. Without ``--fix``, it runs in dry-run mode. It can also run
`Type Perfect <https://getrector.com/blog/introducing-type-perfect-for-extra-safety>`_
as a follow-up PHPStan safety pass when the companion packages are installed.

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

``--type-perfect``
   Runs Type Perfect after Rector using a generated PHPStan config in
   ``tmp/cache/phpstan/type-perfect.neon``.

``--type-perfect-groups=<groups>`` (optional)
   Comma-separated Type Perfect groups to enable. Supported groups:
   ``null_over_false``, ``no_mixed``, and ``narrow_param``.

   Default: ``null_over_false,no_mixed,narrow_param``.

Examples
--------

Run Rector in dry-run mode:

.. code-block:: bash

   composer refactor

Apply fixes automatically:

.. code-block:: bash

   composer refactor --fix

Run Rector and Type Perfect together:

.. code-block:: bash

   composer dev-tools refactor -- --type-perfect

Limit Type Perfect to selected groups:

.. code-block:: bash

   composer dev-tools refactor -- --type-perfect --type-perfect-groups=null_over_false,no_mixed

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
- ``--type-perfect`` requires ``rector/type-perfect`` and
  ``phpstan/extension-installer`` in the consumer project.
- When the consumer already has ``phpstan.neon`` or ``phpstan.neon.dist``,
  the generated Type Perfect config includes it automatically before enabling
  the requested Type Perfect groups.
