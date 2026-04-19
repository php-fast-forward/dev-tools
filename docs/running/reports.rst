Test Reports
============

The ``reports`` command is the packaged "build the site" workflow. It is aimed
at local verification and at the reusable GitHub Actions workflow that
publishes the ``public/`` directory.

What the Command Runs
---------------------

``reports`` executes the following steps:

1. ``docs --target public``
2. ``tests --coverage public/coverage --no-progress --coverage-summary``
3. ``metrics --target public/metrics --junit public/coverage/junit.xml``

Outputs
-------

After a successful run you should expect:

- the documentation site rooted at ``public/``;
- guide pages generated from the local ``docs/`` source;
- coverage reports inside ``public/coverage/``;
- PhpMetrics output inside ``public/metrics/``;
- ``public/coverage/testdox.html`` and ``public/coverage/clover.xml`` for
  human and CI consumption;
- ``public/metrics/report.json`` and ``public/metrics/report-summary.json`` for
  preview artifacts and machine-readable metrics summaries.

Why This Command Matters
------------------------

- it is the last stage of ``standards``;
- the reusable ``reports.yml`` workflow publishes ``public/`` to GitHub Pages;
- the live documentation, coverage, metrics, and Testdox links all depend on this
  directory structure staying stable.

Live Reports
------------

- `Full Documentation Hub <https://php-fast-forward.github.io/dev-tools/>`_
- `Guide Pages <https://php-fast-forward.github.io/dev-tools/guide/>`_
- `Code Coverage <https://php-fast-forward.github.io/dev-tools/coverage/>`_
- `Metrics <https://php-fast-forward.github.io/dev-tools/metrics/>`_
- `Testdox Report <https://php-fast-forward.github.io/dev-tools/coverage/testdox.html>`_
