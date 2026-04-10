Command Classes
===============

All public CLI commands extend
``FastForward\DevTools\Command\AbstractCommand``, which provides path
resolution, configuration fallback, PSR-4 lookup, and child-command dispatch.

.. list-table::
   :header-rows: 1

   * - Class
     - CLI command
     - Responsibility
   * - ``FastForward\DevTools\Command\AbstractCommand``
     - n/a
     - Shared helpers for path resolution, packaged fallback files, PSR-4
       discovery, and subcommand execution.
   * - ``FastForward\DevTools\Command\StandardsCommand``
     - ``standards``
     - Runs the full quality pipeline.
   * - ``FastForward\DevTools\Command\RefactorCommand``
     - ``refactor``
     - Runs Rector with local or packaged configuration.
   * - ``FastForward\DevTools\Command\PhpDocCommand``
     - ``phpdoc``
     - Runs PHP-CS-Fixer and a focused Rector PHPDoc pass.
   * - ``FastForward\DevTools\Command\CodeStyleCommand``
     - ``code-style``
     - Runs Composer Normalize and ECS.
   * - ``FastForward\DevTools\Command\TestsCommand``
     - ``tests``
     - Runs PHPUnit with optional coverage output.
   * - ``FastForward\DevTools\Command\DependenciesCommand``
     - ``dependencies``
     - Reports missing and unused Composer dependencies.
   * - ``FastForward\DevTools\Command\DocsCommand``
     - ``docs``
     - Builds the HTML documentation site.
   * - ``FastForward\DevTools\Command\WikiCommand``
     - ``wiki``
     - Builds Markdown API documentation.
   * - ``FastForward\DevTools\Command\ReportsCommand``
     - ``reports``
     - Combines the documentation build with coverage generation.
   * - ``FastForward\DevTools\Command\SkillsCommand``
     - ``skills``
     - Synchronizes packaged agent skills into ``.agents/skills``.
   * - ``FastForward\DevTools\Command\SyncCommand``
     - ``dev-tools:sync``
     - Synchronizes consumer-facing scripts, automation assets, and packaged
       skills.
   * - ``FastForward\DevTools\Command\GitIgnoreCommand``
     - ``gitignore``
     - Merges and synchronizes .gitignore files.
