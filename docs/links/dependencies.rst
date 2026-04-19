Dependencies
============

The package is deliberately opinionated and bundles the tools it orchestrates
so consumer projects get a consistent baseline.

Runtime and Composer Integration
--------------------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it matters
   * - ``composer/composer`` and ``composer-plugin-api``
     - Provide the Composer plugin API, command integration, and script hooks.
   * - ``phpro/grumphp-shim``
     - Supplies the default GrumPHP executable and configuration referenced by
       ``dev-tools:sync``.

QA and Refactoring
------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it matters
   * - ``symplify/easy-coding-standard``
     - Runs the ECS phase of ``code-style``.
   * - ``ergebnis/composer-normalize``
     - Normalizes ``composer.json`` before ECS runs.
   * - ``rector/rector``
     - Runs the automated refactor and PHPDoc rules.
   * - ``ergebnis/rector-rules``
     - Extends the default Rector configuration with shared rules.
   * - ``friendsofphp/php-cs-fixer``
     - Powers the PHPDoc fixer phase.
   * - ``icanhazstring/composer-unused``
     - Reports unused Composer dependencies in ``dependencies``.
   * - ``shipmonk/composer-dependency-analyser``
     - Reports missing Composer dependencies in ``dependencies``.
   * - ``rector/jack``
     - Previews or applies dependency version updates and enforces the
       outdated dependency threshold.
   * - ``thecodingmachine/safe``
     - Enables optional Safe migration rules when present.

Documentation and Reporting
---------------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it matters
   * - ``phpdocumentor/shim``
     - Generates the HTML documentation site.
   * - ``saggre/phpdocumentor-markdown``
     - Generates the Markdown API pages for the wiki.
   * - ``fast-forward/phpdoc-bootstrap-template``
     - Provides the default HTML theme used by ``docs``.
   * - ``phpmetrics/phpmetrics``
     - Generates the metrics site and JSON artifacts used by ``metrics`` and
       ``reports``.
   * - ``esi/phpunit-coverage-check``
     - Enforces the minimum coverage threshold in the reusable test workflow.

Testing and Local Developer Experience
--------------------------------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it matters
   * - ``phpunit/phpunit``
     - Runs the test suite.
   * - ``phpunit/php-code-coverage``
     - Provides the coverage exporters consumed by ``tests`` and ``reports``.
   * - ``php-parallel-lint/php-parallel-lint``
     - Supports linting and validation in the packaged development workflow.
   * - ``phpspec/prophecy``
     - Provides Prophecy doubles used throughout the packaged test suite.
   * - ``phpspec/prophecy-phpunit``
     - Supports the repository's Prophecy-based test doubles.
   * - ``dg/bypass-finals``
     - Lets the packaged PHPUnit extension bypass final constructs in tests.
   * - ``jolicode/jolinotif``
     - Sends desktop notifications after PHPUnit finishes.

Utility Packages
----------------

.. list-table::
   :header-rows: 1

   * - Package
     - Why it matters
   * - ``fakerphp/faker``
     - Available for test support and generated examples.
   * - ``pyrech/composer-changelogs``
     - Supports changelog tooling in the development environment.
   * - ``symfony/var-dumper`` and ``symfony/var-exporter``
     - Useful development and testing utilities.

Environment Assumptions
-----------------------

- Git is required for wiki submodule creation.
- ``pcov`` is used in the reusable GitHub Actions workflows for coverage.
- ``pcntl`` improves asynchronous notification delivery on platforms that
  support it.
