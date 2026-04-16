Command Classes
===============

All public CLI commands extend ``Composer\Command\BaseCommand`` and receive
dependencies through constructor injection. The architecture uses
``ProcessBuilder`` and ``ProcessQueue`` for fluent process management.

.. list-table::
   :header-rows: 1

   * - Class
     - CLI command
     - Responsibility
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
   * - ``FastForward\DevTools\Console\Command\GitAttributesCommand``
     - ``gitattributes``
     - Manages export-ignore rules in .gitattributes.
   * - ``FastForward\DevTools\Console\Command\LicenseCommand``
     - ``license``
     - Generates a LICENSE file from composer.json license information.
   * - ``FastForward\DevTools\Console\Command\CopyResourceCommand``
     - ``copy-resource``
     - Copies packaged or local resources into the consumer repository.
   * - ``FastForward\DevTools\Console\Command\GitHooksCommand``
     - ``git-hooks``
     - Installs Fast Forward Git hooks.
   * - ``FastForward\DevTools\Console\Command\UpdateComposerJsonCommand``
     - ``update-composer-json``
     - Updates the composer.json file to match the packaged schema.
