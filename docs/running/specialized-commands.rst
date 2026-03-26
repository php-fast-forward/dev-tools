Specialized Commands
====================

The unified toolkit exposes robust standalone commands mapped directly to discrete operational behaviors for enhanced granularity natively.

1. Running Tests (``tests``)
----------------------------

Safely executes the meticulously configured PHPUnit test suite against your codebase exactly. It automatically allocates valid configuration routes natively.

.. code-block:: bash

   composer dev-tools tests

*Supports passing ``--coverage`` to dictate HTML frontend reporting.*

2. Auditing Code Style (``code-style``)
---------------------------------------

Analyzes and transparently validates adherence to standard coding constraints leveraging EasyCodingStandard (ECS) and Composer Normalize.

.. code-block:: bash

   composer dev-tools code-style

*Supports passing ``--fix`` to adjust syntax automatically.*

3. Automated Refactoring (``refactor``)
---------------------------------------

Triggers abstract syntax tree inspections evaluating logical components internally. It strictly executes architecture upgrades efficiently and securely via Rector natively.

.. code-block:: bash

   composer dev-tools refactor

*Supports passing ``--fix`` to apply transformations to code files.*

4. Generating PHPDoc (``phpdoc``)
----------------------------------

Intelligently audits your project methods to identify lacking definitions, executing Rector rules conforming to `RFC 2119 <https://datatracker.ietf.org/doc/html/rfc2119>`_ dynamically.

.. code-block:: bash

   composer dev-tools phpdoc

5. HTML Documentation (``docs``)
--------------------------------

Reads defined PSR-4 paths logically extracting explicit structures accurately. It deploys HTML documentation describing your internal structural hierarchy using phpDocumentor.

.. code-block:: bash

   composer dev-tools docs


6. Wiki Markdown Documentation (``wiki``)
-----------------------------------------

Generates API documentation in Markdown format, ideal for GitHub wikis or other collaborative documentation platforms. The output is placed in the ``.github/wiki`` directory, making it easy to keep your project wiki up to date with the latest API changes.

.. code-block:: bash

   composer dev-tools wiki

7. Reports Output (``reports``)
-------------------------------

Structurally consolidates distinct reporting commands, accurately aggregating testing coverage and docs into clean visual output components.

.. code-block:: bash

   composer dev-tools reports

8. Install (``install``)
------------------------

Adds or updates dev-tools scripts in your ``composer.json``, copies reusable GitHub Actions workflows, ensures the ``.editorconfig`` file is present and up to date, and guarantees the repository wiki is present as a git submodule in ``.github/wiki``. This command helps standardize your team's workflow and automation setup.

.. code-block:: bash

   composer dev-tools install
