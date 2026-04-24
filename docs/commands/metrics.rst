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
   composer metrics [options]
   vendor/bin/dev-tools metrics [options]

Options
-------

``--working-dir=<path>``
   Composer's inherited working-directory option. Use it when you invoke the
   command through Composer and want to analyze another checkout without
   changing your current shell session first.

   Default: the current working directory.

``--exclude=<list>``
   Comma-separated directories that should be excluded from analysis.

   Default:
   ``vendor,test,tests,tmp,cache,spec,build,.dev-tools,backup,resources``.

``--target=<directory>``
   Output directory for the generated metrics reports.

   Default: ``.dev-tools/metrics``.

   The command writes:

   - the HTML report to the target directory itself;
   - ``report.json`` inside the target directory;
   - ``report-summary.json`` inside the target directory.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

``--progress``
   Enable progress output from PhpMetrics.

Examples
--------

Analyze the current repository with defaults:

.. code-block:: bash

   composer metrics

Generate an HTML report for manual inspection:

.. code-block:: bash

   composer metrics --target=.dev-tools/metrics

Generate the full metrics artifact set for CI previews:

.. code-block:: bash

   composer metrics --target=.dev-tools/metrics

Analyze another checkout through Composer's inherited working directory:

.. code-block:: bash

   composer --working-dir=packages/example metrics

Behavior
--------

- the command derives ``report.json`` and ``report-summary.json`` from the
  selected ``--target`` directory;
- progress output is disabled by default; use ``--progress`` to re-enable it in
  text mode;
- ``--json`` and ``--pretty-json`` keep DevTools itself structured while
  running PhpMetrics in a quieter mode to avoid polluting the captured payload;
- it runs PhpMetrics through the active PHP binary and suppresses PhpMetrics
  deprecation notices emitted by the dependency itself;
- it disables PhpMetrics' Composer package freshness lookup so metrics runs do
  not block on Packagist availability or local network state.
