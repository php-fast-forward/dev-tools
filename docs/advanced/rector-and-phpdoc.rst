Rector and PHPDoc Automation
============================

The package uses two different Rector entry points, and that difference matters
when you are trying to understand why a rule did or did not run.

``refactor`` Versus ``phpdoc``
------------------------------

- ``refactor`` uses the full ``rector.php`` file.
- ``phpdoc`` runs PHP-CS-Fixer first and then executes Rector with
  ``--only \FastForward\DevTools\Rector\AddMissingMethodPhpDocRector``.

Rules Shipped by the Package
----------------------------

.. list-table::
   :header-rows: 1

   * - Rule
     - Enabled in packaged ``rector.php``
     - Used directly by ``phpdoc``
     - Purpose
   * - ``FastForward\DevTools\Rector\AddMissingMethodPhpDocRector``
     - Yes
     - Yes
     - Adds ``@param``, ``@return``, and ``@throws`` tags when they can be
       inferred.
   * - ``FastForward\DevTools\Rector\RemoveEmptyDocBlockRector``
     - Yes
     - No
     - Removes empty docblocks left behind by refactors.
   * - ``FastForward\DevTools\Rector\AddMissingClassPhpDocRector``
     - No
     - No
     - Available for projects that want to opt in manually.

Other Packaged Rector Behavior
------------------------------

The default ``rector.php`` also loads shared Rector sets, imports names,
removes unused imports, skips generated directories, and enables Safe migration
rules when ``thecodingmachine/safe`` is installed.

Type Perfect in the Refactor Workflow
-------------------------------------

The ``refactor`` command can optionally run Type Perfect immediately after the
Rector pass:

.. code-block:: bash

   composer dev-tools refactor -- --type-perfect

The Fast Forward integration path is intentionally opt-in. It expects
``rector/type-perfect`` and ``phpstan/extension-installer`` to be installed in
the consumer project. When the consumer already has ``phpstan.neon`` or
``phpstan.neon.dist``, DevTools includes that file automatically in the
generated ``tmp/cache/phpstan/type-perfect.neon`` and then enables the selected
Type Perfect groups there.

Why ``.docheader`` Appears Automatically
----------------------------------------

The ``phpdoc`` command creates ``.docheader`` in the consumer root when it is
missing. The template comes from the packaged file and the package name is
rewritten to match the current project whenever Composer metadata is
available.
