reports
=======

Generates the frontpage for Fast Forward documentation.

Description
-----------

The ``reports`` command generates the documentation frontpage and runs tests with
coverage. It combines:

- ``docs --target`` - generates API documentation
- ``tests --coverage`` - generates test coverage reports
- optionally ``metrics --report-html`` - generates PhpMetrics HTML reports

These are run in parallel for efficiency.

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
   Default: ``public``.

``--coverage, -c`` (optional)
   The target directory for the generated test coverage report.
   Default: ``public/coverage``.

``--metrics`` (optional)
   Generate the metrics HTML report. When passed without a value, the report is
   generated in ``public/metrics``.

Examples
--------

Generate reports with defaults:

.. code-block:: bash

   composer reports

Generate to custom directories:

.. code-block:: bash

   composer reports --target=build --coverage=build/coverage

Generate reports including metrics:

.. code-block:: bash

   composer reports --metrics
   composer reports --metrics=build/metrics

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Both docs and coverage generated.
   * - 1
     - Failure. One or both commands failed.

Behavior
---------

- Runs ``docs`` and ``tests --coverage`` in parallel.
- Runs ``metrics --report-html`` in parallel when ``--metrics`` is enabled.
- Runs tests with ``--no-progress`` and ``--coverage-summary`` so report builds
  keep PHPUnit output concise.
- Used by the ``standards`` command as the final phase.
- This is the reporting stage used by GitHub Pages.
