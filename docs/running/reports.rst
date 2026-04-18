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

Outputs
-------

After a successful run you should expect:

- the documentation site rooted at ``public/``;
- guide pages generated from the local ``docs/`` source;
- coverage reports inside ``public/coverage/``;
- ``public/coverage/testdox.html`` and ``public/coverage/clover.xml`` for
  human and CI consumption.

Why This Command Matters
------------------------

- it is the last stage of ``standards``;
- the reusable ``reports.yml`` workflow publishes ``public/`` to GitHub Pages;
- the live documentation, coverage, and Testdox links all depend on this
  directory structure staying stable.

Live Reports
------------

- `Full Documentation Hub <https://php-fast-forward.github.io/dev-tools/>`_
- `Guide Pages <https://php-fast-forward.github.io/dev-tools/guide/>`_
- `Code Coverage <https://php-fast-forward.github.io/dev-tools/coverage/>`_
- `Testdox Report <https://php-fast-forward.github.io/dev-tools/coverage/testdox.html>`_
