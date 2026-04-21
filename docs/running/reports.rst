Test Reports
============

The ``reports`` command is the packaged "build the site" workflow. It is aimed
at local verification and at the reusable GitHub Actions workflow that
publishes a generated reports directory to GitHub Pages.

What the Command Runs
---------------------

``reports`` executes the following steps:

1. ``docs --target .dev-tools``
2. ``tests --coverage .dev-tools/coverage --coverage-summary``
3. ``metrics --target .dev-tools/metrics --junit .dev-tools/coverage/junit.xml``

Outputs
-------

After a successful run you should expect:

- the documentation site rooted at ``.dev-tools/``;
- guide pages generated from the local ``docs/`` source;
- coverage reports inside ``.dev-tools/coverage/``;
- PhpMetrics output inside ``.dev-tools/metrics/``;
- ``.dev-tools/coverage/testdox.html`` and ``.dev-tools/coverage/clover.xml`` for
  human and CI consumption;
- ``.dev-tools/metrics/report.json`` and ``.dev-tools/metrics/report-summary.json`` for
  preview artifacts and machine-readable metrics summaries.

Why This Command Matters
------------------------

- it is the last stage of ``standards``;
- the reusable ``reports.yml`` workflow publishes the generated reports
  directory to GitHub Pages;
- the live documentation, coverage, metrics, and Testdox links all depend on this
  directory structure staying stable.

Live Reports
------------

- `Full Documentation Hub <https://php-fast-forward.github.io/dev-tools/>`_
- `Guide Pages <https://php-fast-forward.github.io/dev-tools/guide/>`_
- `Code Coverage <https://php-fast-forward.github.io/dev-tools/coverage/>`_
- `Metrics <https://php-fast-forward.github.io/dev-tools/metrics/>`_
- `Testdox Report <https://php-fast-forward.github.io/dev-tools/coverage/testdox.html>`_
