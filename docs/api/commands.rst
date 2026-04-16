Command Classes
===============

All public CLI commands extend
``FastForward\DevTools\Console\Command\AbstractCommand``, which provides path
resolution, configuration fallback, PSR-4 lookup, and child-command dispatch.

.. list-table::
   :header-rows: 1

   * - Class
     - CLI command
     - Responsibility
   * - ``FastForward\DevTools\Console\Command\AbstractCommand``
     - n/a
     - Shared helpers for path resolution, packaged fallback files, PSR-4
       discovery, and subcommand execution.
   * - ``FastForward\DevTools\Console\Command\StandardsCommand``
     - ``standards``
     - Runs the full quality pipeline.
   * - ``FastForward\DevTools\Console\Command\RefactorCommand``
     - ``refactor``
     - Runs Rector with local or packaged configuration.
   * - ``FastForward\DevTools\Console\Command\PhpDocCommand``
     - ``phpdoc``
     - Runs PHP-CS-Fixer and a focused Rector PHPDoc pass.
   * - ``FastForward\DevTools\Console\Command\CodeStyleCommand``
     - ``code-style``
     - Runs Composer Normalize and ECS.
   * - ``FastForward\DevTools\Console\Command\TestsCommand``
     - ``tests``
     - Runs PHPUnit with optional coverage output.
   * - ``FastForward\DevTools\Console\Command\DependenciesCommand``
     - ``dependencies``
     - Reports missing and unused Composer dependencies.
   * - ``FastForward\DevTools\Console\Command\DocsCommand``
     - ``docs``
     - Builds the HTML documentation site.
   * - ``FastForward\DevTools\Console\Command\WikiCommand``
     - ``wiki``
     - Builds Markdown API documentation.
   * - ``FastForward\DevTools\Console\Command\ReportsCommand``
     - ``reports``
     - Combines the documentation build with coverage generation.
   * - ``FastForward\DevTools\Console\Command\SkillsCommand``
     - ``skills``
     - Synchronizes packaged agent skills into ``.agents/skills``.
   * - ``FastForward\DevTools\Console\Command\SyncCommand``
     - ``dev-tools:sync``
     - Synchronizes consumer-facing scripts, automation assets, and packaged
       skills.
    * - ``FastForward\DevTools\Console\Command\GitIgnoreCommand``
      - ``gitignore``
      - Merges and synchronizes .gitignore files.
    * - ``FastForward\DevTools\Console\Command\LicenseCommand``
      - ``license``
      - Generates a LICENSE file from composer.json license information.
