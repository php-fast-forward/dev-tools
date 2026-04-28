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
- Optional progress and coverage-text verbosity control

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
   Path to the PHPUnit cache directory. Default: selected workspace cache
   directory, usually ``.dev-tools/cache/phpunit``.

``--cache``
   Force PHPUnit result caching on for this run.

``--no-cache``
   Force PHPUnit result caching off for this run.

``--progress``
   Enable PHPUnit progress output.

``--coverage, -c`` (optional)
   Generate code coverage reports. If a path is provided, reports are saved there.
   Without a path, reports are saved to the cache directory.

``--coverage-summary``
   When coverage text is generated, show only the summary table.

``--filter, -f`` (optional)
   Filter which tests to run based on a pattern (regex supported).

``--min-coverage`` (required)
   Minimum line coverage percentage required for a successful run (0-100).

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.
   This also suppresses PHPUnit progress output automatically so the JSON
   payload is not polluted by transient progress rendering.

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

   composer tests --coverage=.dev-tools/coverage

Run with concise coverage text output:

.. code-block:: bash

   composer tests --coverage=.dev-tools/coverage --coverage-summary

Run tests matching a pattern:

.. code-block:: bash

   composer tests -- --filter=EventTracerTest

Run with minimum coverage enforcement:

.. code-block:: bash

   composer tests --min-coverage=80

Run without cache:

.. code-block:: bash

   composer tests --no-cache

Force cache on explicitly:

.. code-block:: bash

   composer tests --cache

Run with PHPUnit progress output enabled:

.. code-block:: bash

   composer tests --progress

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
- Cache stays enabled by default; omit both flags to keep the command default,
  pass ``--cache`` to force it on, and pass ``--no-cache`` to force it off.
- When ``--cache-dir`` is omitted, PHPUnit keeps its default cache directory.
  The option only affects the result cache when caching is enabled.
- ``--coverage-summary`` forwards PHPUnit's ``--only-summary-for-coverage-text``
  only when coverage text output is generated.
- progress output is disabled by default.
- ``--json`` and ``--pretty-json`` keep progress output disabled so the
  structured payload stays clean, even when ``--progress`` is provided.
- The command fails if minimum coverage is not met (when ``--min-coverage`` is set).
- The packaged configuration registers the DevTools PHPUnit extension.
