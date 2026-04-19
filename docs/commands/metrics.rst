metrics
=======

Analyzes code metrics with PhpMetrics.

Overview
--------

The ``metrics`` command runs `PhpMetrics <https://phpmetrics.github.io/website/>`_
against the selected source directory, generates a JSON report, and prints a
reduced summary with:

- average cyclomatic complexity by class;
- average maintainability index by class;
- number of classes analyzed;
- number of functions analyzed.

Usage
-----

.. code-block:: bash

   composer metrics
   composer dev-tools metrics -- [options]
   vendor/bin/dev-tools metrics [options]

Options
-------

``--src=<path>``
   Source directory to analyze.

   Default: ``src``.

``--exclude=<list>``
   Comma-separated directories that should be excluded from analysis.

   Default:
   ``vendor,test,Test,tests,Tests,testing,Testing,bower_components,node_modules,cache,spec,build``.

``--report-html=<directory>``
   Optional output directory for the generated HTML report.

``--report-json=<file>``
   Optional output file for the generated JSON report.

``--cache-dir=<directory>``
   Cache directory used for temporary JSON reports when ``--report-json`` is
   not provided.

   Default: ``tmp/cache/phpmetrics``.

Examples
--------

Generate the reduced summary with defaults:

.. code-block:: bash

   composer metrics

Generate an HTML report for manual inspection:

.. code-block:: bash

   composer dev-tools metrics -- --report-html=build/metrics

Generate both JSON and HTML reports for CI artifacts:

.. code-block:: bash

   vendor/bin/dev-tools metrics --report-json=build/metrics.json --report-html=build/metrics

Behavior
--------

- the command fails early when ``vendor/bin/phpmetrics`` is not installed;
- the source directory must exist;
- the reduced summary is derived from the generated PhpMetrics JSON report;
- optional HTML and JSON report destinations are created before execution.
