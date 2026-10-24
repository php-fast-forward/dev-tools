Command Classes
===============

All public CLI commands are Symfony Console commands resolved lazily through
``DevToolsCommandLoader``. Command classes receive their collaborators from
the shared ``DevToolsServiceProvider`` container, while orchestration commands
such as ``standards`` dispatch other commands through the console application
itself.
The architecture also relies on ``ProcessBuilder`` and ``ProcessQueue`` for
fluent process management where subprocess execution is needed.

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
     - ``standards:phpdoc``
     - Runs PHP-CS-Fixer and a focused Rector PHPDoc pass. Supported aliases:
       ``phpdoc``, ``docheader`` and ``php-cs-fixer``.
   * - ``FastForward\DevTools\Console\Command\CodeStyleCommand``
     - ``code-style``
     - Runs Composer Normalize and ECS.
   * - ``FastForward\DevTools\Console\Command\TestsCommand``
     - ``tests``
     - Runs PHPUnit with optional coverage output.
   * - ``FastForward\DevTools\Console\Command\DependenciesCommand``
     - ``dependencies``
     - Reports missing, unused, misplaced, and outdated Composer dependencies.
   * - ``FastForward\DevTools\Console\Command\MetricsCommand``
     - ``metrics``
     - Builds the PhpMetrics site and JSON artifacts for the current project.
   * - ``FastForward\DevTools\Console\Command\DocsCommand``
     - ``docs``
     - Builds the HTML documentation site.
   * - ``FastForward\DevTools\Console\Command\WikiCommand``
     - ``wiki``
     - Builds Markdown API documentation.
   * - ``FastForward\DevTools\Console\Command\ReportsCommand``
     - ``reports``
     - Combines documentation, coverage, and metrics generation.
   * - ``FastForward\DevTools\Console\Command\FundingCommand``
     - ``funding``
     - Synchronizes managed funding metadata between Composer and GitHub files.
   * - ``FastForward\DevTools\Console\Command\CodeOwnersCommand``
     - ``codeowners``
     - Generates managed ``.github/CODEOWNERS`` content from project metadata.
   * - ``FastForward\DevTools\Console\Command\SyncCommand``
     - ``dev-tools:sync``
     - Synchronizes consumer-facing scripts, metadata, workflows and defaults.
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
   * - ``FastForward\DevTools\Console\Command\SelfUpdateCommand``
     - ``dev-tools:self-update``
     - Updates the local or global DevTools installation through Composer.
