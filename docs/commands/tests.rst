tests
=====

Runs PHPUnit tests with configurable options.

Description
-----------

The ``tests`` command executes PHPUnit with the resolved configuration.
It supports:

- Running tests in a specific directory
- Generating code coverage reports (HTML, Testdox, Clover, PHP)
- Enforcing minimum coverage thresholds
- Filtering tests by pattern
- Cache management

Usage
-----

.. code-block:: bash

   composer tests
   composer tests [path] [options]
   composer dev-tools tests -- [path] [options]
   vendor/bin/dev-tools tests [path] [options]

Arguments
---------

``path`` (optional)
   Path to the tests directory. Default: ``./tests``.

Options
-------

``--bootstrap, -b`` (optional)
   Path to the bootstrap file. Default: ``./vendor/autoload.php``.

``--cache-dir`` (optional)
   Path to the PHPUnit cache directory. Default: ``./tmp/cache/phpunit``.

``--no-cache``
   Disable PHPUnit caching.

``--coverage, -c`` (optional)
   Generate code coverage reports. If a path is provided, reports are saved there.
   Without a path, reports are saved to the cache directory.

``--filter, -f`` (optional)
   Filter which tests to run based on a pattern (regex supported).

``--min-coverage`` (required)
   Minimum line coverage percentage required for a successful run (0-100).

Examples
--------

Run all tests:

.. code-block:: bash

   composer tests

Run tests in a specific directory:

.. code-block:: bash

   composer tests ./tests/unit

Run with coverage report:

.. code-block:: bash

   composer tests --coverage=public/coverage

Run tests matching a pattern:

.. code-block:: bash

   composer tests -- --filter=EventTracerTest

Run with minimum coverage enforcement:

.. code-block:: bash

   composer tests --min-coverage=80

Run without cache:

.. code-block:: bash

   composer tests --no-cache

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All tests passed and coverage met (if configured).
   * - 1
     - Failure. Tests failed or coverage below minimum.
   * - 2
     - Invalid configuration or options.

Behavior
---------

- Local ``phpunit.xml`` is preferred over the packaged default.
- Coverage filters are automatically applied to all PSR-4 paths from composer.json.
- Multiple coverage formats are generated: HTML, Testdox HTML, Clover XML, and PHP.
- The command fails if minimum coverage is not met (when ``--min-coverage`` is set).
- The packaged configuration registers the DevTools PHPUnit extension.
