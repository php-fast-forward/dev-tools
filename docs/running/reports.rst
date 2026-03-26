Test Reports
============

FastForward DevTools provides a unified mechanism to generate and aggregate multiple types of reports, including API documentation and testing metrics, into a clean and accessible directory structure.

The Reports Command (``reports``)
---------------------------------

The ``reports`` command is the high-level orchestrator that sequentially runs the documentation and testing suites with coverage enabled. It then generates a central entry point (``index.html``) in your project's ``public/`` directory.

.. code-block:: bash

   composer dev-tools reports

Unified Output Structure
------------------------

When you run the reports command, the following structure is created in your project root:

.. code-block:: text

   public/
   ├── index.html        # The main frontpage linking to all reports
   ├── docs/             # Generated HTML API documentation (phpDocumentor)
   └── coverage/         # Testing reports (PHPUnit)
       ├── index.html    # Detailed HTML Code Coverage report
       ├── testdox.html  # Human-readable Testdox execution report
       ├── clover.xml    # XML coverage for CI integration
       └── coverage.php  # Raw PHP coverage data

Code Coverage
-------------

The Code Coverage report provides a visual representation of which lines of your code are executed by your tests. It helps identify untested areas of the codebase.

- **Location**: ``public/coverage/index.html``
- **Generation**: Triggered by ``composer dev-tools reports`` or ``composer dev-tools tests --coverage=public/coverage``.

Testdox
-------

The Testdox report transforms your technical test case names into a human-readable list of behavioral expectations, serving as a form of living documentation for your project's functionality.

- **Location**: ``public/coverage/testdox.html``
- **Generation**: Automatically included whenever coverage is generated via the ``reports`` or ``tests`` commands.

Live Reports (GitHub Pages)
---------------------------

For the latest status of the ``main`` branch, you can access the live reports deployed via GitHub Actions:

- **Full Documentation Hub**: `https://php-fast-forward.github.io/dev-tools/ <https://php-fast-forward.github.io/dev-tools/>`_
- **API Documentation**: `https://php-fast-forward.github.io/dev-tools/docs/ <https://php-fast-forward.github.io/dev-tools/docs/>`_
- **Code Coverage**: `https://php-fast-forward.github.io/dev-tools/coverage/ <https://php-fast-forward.github.io/dev-tools/coverage/>`_
- **Testdox Report**: `https://php-fast-forward.github.io/dev-tools/coverage/testdox.html <https://php-fast-forward.github.io/dev-tools/coverage/testdox.html>`_
