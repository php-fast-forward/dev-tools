Command Classes
===============

All public CLI commands extend ``Composer\Command\BaseCommand``. Most command
classes are resolved lazily through ``DevToolsCommandLoader`` and receive
their collaborators from the shared ``DevToolsServiceProvider`` container,
while orchestration commands such as ``standards`` dispatch other commands
through the console application itself. The architecture also relies on
``ProcessBuilder`` and ``ProcessQueue`` for fluent process management where
subprocess execution is needed.

.. list-table::
   :header-rows: 1

   * - Class
     - CLI command
     - Responsibility
   * - ``FastForward\DevTools\Console\Command\StandardsCommand``
     - ``standards``
     - Runs the full quality pipeline.
   * - ``FastForward\DevTools\Console\Command\ChangelogEntryCommand``
     - ``changelog:entry``
     - Adds a changelog entry to ``Unreleased`` or a published version.
   * - ``FastForward\DevTools\Console\Command\ChangelogCheckCommand``
     - ``changelog:check``
     - Verifies that a branch adds meaningful unreleased changelog changes.
   * - ``FastForward\DevTools\Console\Command\ChangelogNextVersionCommand``
     - ``changelog:next-version``
     - Infers the next semantic version from ``Unreleased``.
   * - ``FastForward\DevTools\Console\Command\ChangelogPromoteCommand``
     - ``changelog:promote``
     - Promotes ``Unreleased`` entries into a published release section.
   * - ``FastForward\DevTools\Console\Command\ChangelogShowCommand``
     - ``changelog:show``
     - Renders the notes body for a published changelog release.
   * - ``FastForward\DevTools\Console\Command\RefactorCommand``
     - ``refactor``
     - Runs Rector with local or packaged configuration.
   * - ``FastForward\DevTools\Console\Command\PhpDocCommand``
     - ``standards:docheader``
     - Runs PHP-CS-Fixer and a focused Rector PHPDoc pass. Supported aliases:
       ``docheader`` and ``php-cs-fixer``.
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
   * - ``FastForward\DevTools\Console\Command\AgentsCommand``
     - ``agents``
     - Synchronizes packaged project agents into ``.agents/agents``.
   * - ``FastForward\DevTools\Console\Command\SkillsCommand``
     - ``skills``
     - Synchronizes packaged agent skills into ``.agents/skills``.
   * - ``FastForward\DevTools\Console\Command\FundingCommand``
     - ``funding``
     - Synchronizes managed funding metadata between Composer and GitHub files.
   * - ``FastForward\DevTools\Console\Command\CodeOwnersCommand``
     - ``codeowners``
     - Generates managed ``.github/CODEOWNERS`` content from project metadata.
   * - ``FastForward\DevTools\Console\Command\SyncCommand``
     - ``dev-tools:sync``
     - Synchronizes consumer-facing scripts, automation assets, and packaged
       skills and project agents.
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
