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

``--target=<directory>``
   Output directory for the generated metrics reports.

   Default: ``public/metrics``.

   The command writes:

   - the HTML report to the target directory itself;
   - ``report.json`` inside the target directory;
   - ``report-summary.json`` inside the target directory.

Examples
--------

Analyze the current repository with defaults:

.. code-block:: bash

   composer metrics

Generate an HTML report for manual inspection:

.. code-block:: bash

   composer dev-tools metrics -- --target=build/metrics

Generate the full metrics artifact set for CI previews:

.. code-block:: bash

   vendor/bin/dev-tools metrics --target=build/metrics

Behavior
--------

- the command derives ``report.json`` and ``report-summary.json`` from the
  selected ``--target`` directory;
- it runs PhpMetrics through the active PHP binary and suppresses PhpMetrics
  deprecation notices emitted by the dependency itself.
