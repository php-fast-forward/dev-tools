reports
=======

Generates the frontpage for Fast Forward documentation.

Description
-----------

The ``reports`` command generates the documentation frontpage, runs tests with
coverage, and generates code metrics. It combines:

- ``docs --target`` - generates API documentation
- ``tests --coverage`` - generates test coverage reports
- ``metrics --target`` - generates PhpMetrics HTML and JSON reports

The documentation build runs in parallel with PHPUnit, and the metrics step
runs after PHPUnit so it can reuse the generated JUnit report.

Usage
-----

.. code-block:: bash

   composer reports
   composer reports [options]
   vendor/bin/dev-tools reports -- [options]

Options
-------

``--target`` (optional)
   The target directory for the generated documentation.
   Default: ``build``.

``--coverage, -c`` (optional)
   The target directory for the generated test coverage report.
   Default: ``build/coverage``.

``--metrics`` (optional)
   The target directory for the generated metrics report.
   Default: ``build/metrics``.

Examples
--------

Generate reports with defaults:

.. code-block:: bash

   composer reports

Generate to custom directories:

.. code-block:: bash

   composer reports --target=build --coverage=build/coverage

Generate reports with a custom metrics directory:

.. code-block:: bash

   composer reports --metrics=build/metrics

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Documentation, coverage, and metrics generated successfully.
   * - 1
     - Failure. One or more report stages failed.

Behavior
---------

- Runs ``docs`` in parallel with ``tests --coverage``.
- Runs ``metrics --target`` after tests so the JUnit report is available.
- Runs tests with ``--no-progress`` and ``--coverage-summary`` so report builds
  keep PHPUnit output concise.
- Passes ``--junit <coverage>/junit.xml`` to the metrics step.
- Used by the ``standards`` command as the final phase.
- This is the reporting stage used by GitHub Pages.
