metrics
=======

Analyzes code metrics with PhpMetrics.

Overview
--------

The ``metrics`` command runs `PhpMetrics <https://phpmetrics.github.io/website/>`_
against the current working directory and forwards the requested report
artifacts.

Usage
-----

.. code-block:: bash

   composer metrics
   composer dev-tools metrics -- [options]
   vendor/bin/dev-tools metrics [options]

Options
-------

``--working-dir=<path>``
   Composer's inherited working-directory option. Use it when you want to run
   the command from another directory without changing your current shell
   session.

   Default: the current working directory.

``--exclude=<list>``
   Comma-separated directories that should be excluded from analysis.

   Default:
   ``vendor,test,tests,tmp,cache,spec,build,backup,resources``.

``--report-html=<directory>``
   Optional output directory for the generated HTML report.

``--report-json=<file>``
   Optional output file for the generated JSON report.

``--report-summary-json=<file>``
   Optional output file for the generated summary JSON report.

Examples
--------

Analyze the current repository with defaults:

.. code-block:: bash

   composer metrics

Generate an HTML report for manual inspection:

.. code-block:: bash

   composer dev-tools metrics -- --report-html=build/metrics

Generate JSON and HTML reports for CI artifacts:

.. code-block:: bash

   vendor/bin/dev-tools metrics --report-json=build/metrics.json --report-html=build/metrics

Behavior
--------

- the command forwards report options directly to PhpMetrics;
- it runs PhpMetrics through the active PHP binary and suppresses PhpMetrics
  deprecation notices emitted by the dependency itself.
