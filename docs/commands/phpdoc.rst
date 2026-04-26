phpdoc
======

Checks and fixes PHPDoc comments.

Description
-----------

The ``phpdoc`` command coordinates PHPDoc checking and fixing using:
(alias ``docheader`` and ``php-cs-fixer``)

- PHP-CS-Fixer - fixes PHPDoc formatting
- Rector with ``AddMissingMethodPhpDocRector`` - adds missing method PHPDoc

It also creates the ``.docheader`` template from repository metadata when missing.

Usage
-----

.. code-block:: bash

   composer phpdoc
   composer phpdoc [options]
   composer dev-tools phpdoc -- [options]
   vendor/bin/dev-tools phpdoc [options]

Arguments
---------

``path`` (optional)
   Path to the file or directory to check. Default: ``.``.

Options
-------

``--fix, -f``
   Automatically fix PHPDoc issues. Without this option, runs in dry-run mode.

``--cache-dir`` (optional)
   Path to the cache directory for PHP-CS-Fixer. Default: ``.dev-tools/cache/php-cs-fixer``.

``--cache``
   Force PHP-CS-Fixer caching on for this run.

``--no-cache``
   Force PHP-CS-Fixer caching off for this run.

``--progress``
   Enable progress output from PHP-CS-Fixer and the Rector phase.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Check PHPDocs (dry-run):

.. code-block:: bash

   composer phpdoc

Fix PHPDocs automatically:

.. code-block:: bash

   composer phpdoc --fix

Check specific directory:

.. code-block:: bash

   composer phpdoc ./src

Check without cache:

.. code-block:: bash

   composer phpdoc --no-cache

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All PHPDocs valid or fixes applied.
   * - 1
     - Failure. PHPDoc issues found.

Behavior
---------

- Creates ``.docheader`` from the packaged template when the file is missing.
- Uses ``.php-cs-fixer.dist.php`` and ``rector.php`` through local-first fallback.
- The Rector phase explicitly runs ``FastForward\DevTools\Rector\AddMissingMethodPhpDocRector``.
- Cache stays enabled by default; omit both flags to keep the command default,
  pass ``--cache`` to force it on, and pass ``--no-cache`` to force it off.
- When ``--cache-dir`` is omitted, PHP-CS-Fixer keeps its default cache
  directory. The option only affects PHP-CS-Fixer when caching is enabled.
- Progress output is disabled by default; use ``--progress`` to re-enable it in
  text mode.
- ``--json`` and ``--pretty-json`` forward JSON mode to PHP-CS-Fixer and
  Rector while disabling their progress rendering.
- Uses ``--dry-run`` mode unless ``--fix`` is specified.
