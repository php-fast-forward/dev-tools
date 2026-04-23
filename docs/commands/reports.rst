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
   Default: ``.dev-tools``.

``--coverage, -c`` (optional)
   The target directory for the generated test coverage report.
   Default: ``.dev-tools/coverage``.

``--metrics`` (optional)
   The target directory for the generated metrics report.
   Default: ``.dev-tools/metrics``.

``--cache-dir`` (optional)
   Base cache directory for nested ``docs`` and ``tests`` caches.
   When omitted, each nested tool keeps its own default cache directory.

``--cache``
   Force cache-aware nested documentation and test steps to keep caching enabled.

``--no-cache``
   Force cache-aware nested documentation and test steps to disable caching.

``--progress``
   Enable progress output in nested ``docs``, ``tests``, and ``metrics`` steps.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate reports with defaults:

.. code-block:: bash

   composer reports

Generate to custom directories:

.. code-block:: bash

   composer reports --target=.dev-tools --coverage=.dev-tools/coverage

Generate reports with a custom metrics directory:

.. code-block:: bash

   composer reports --metrics=.dev-tools/metrics

Generate reports with cache disabled for nested docs and tests:

.. code-block:: bash

   composer reports --no-cache

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
- Cache stays enabled by default for nested cache-aware steps; omit both flags
  to keep the command default, pass ``--cache`` to force it on, and pass
  ``--no-cache`` to force it off.
- The explicit cache intent is propagated to the nested ``docs`` and ``tests``
  steps. The ``metrics`` step does not consume this contract.
- When ``--cache-dir`` is provided, ``docs`` and ``tests`` receive nested cache
  directories under that base path. When it is omitted, each nested tool keeps
  its own default cache directory.
- Runs tests with progress disabled by default and ``--coverage-summary`` so
  report builds keep PHPUnit output concise.
- Progress output stays disabled by default across nested steps; use
  ``--progress`` to re-enable it for human-readable runs.
- When ``--json`` or ``--pretty-json`` is active, it forwards JSON mode to the
  ``docs``, ``tests``, and ``metrics`` subprocesses and suppresses transient
  progress output where those tools support it.
- Passes ``--junit <coverage>/junit.xml`` to the metrics step.
- Used by the ``standards`` command as the final phase.
- This is the reporting stage used by GitHub Pages.
- In GitHub Actions, the queued subprocess output is emitted inside collapsible
  workflow groups for easier log navigation.
