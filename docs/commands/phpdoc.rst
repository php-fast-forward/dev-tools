phpdoc
======

Checks and fixes PHPDoc comments.

Description
-----------

The ``phpdoc`` command coordinates PHPDoc checking and fixing using:

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
   Path to the cache directory for PHP-CS-Fixer. Default: ``tmp/cache/php-cs-fixer``.

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
- Uses ``--dry-run`` mode unless ``--fix`` is specified.
