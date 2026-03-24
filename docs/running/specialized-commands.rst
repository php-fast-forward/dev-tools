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

5. API Documentation (``docs``)
-------------------------------

Reads defined PSR-4 paths logically extracting explicit structures accurately. It deploys Markdown documentation describing your internal structural hierarchy inside ``docs/wiki``.

.. code-block:: bash

   composer dev-tools docs

6. Reports Output (``reports``)
-------------------------------

Structurally consolidates distinct reporting commands, accurately aggregating testing coverage and docs into clean visual output components.

.. code-block:: bash

   composer dev-tools reports
