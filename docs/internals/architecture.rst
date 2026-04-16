Architecture and Command Lifecycle
=================================

The package has two connected execution models: local command execution and
consumer repository synchronization.

Local Command Lifecycle
-----------------------

1. ``bin/dev-tools`` loads ``bin/dev-tools.php``.
2. ``bin/dev-tools.php`` prefers the consumer ``vendor/autoload.php`` and
   falls back to the package autoloader.
3. ``FastForward\DevTools\DevTools`` boots the command registry from
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.
4. ``standards`` is used as the default command when no explicit command name
   is given.
5. Commands receive dependencies through constructor injection from
   ``DevToolsServiceProvider``.

Consumer Synchronization Lifecycle
----------------------------------

1. A library installs ``fast-forward/dev-tools`` as a development dependency.
2. Composer loads ``FastForward\DevTools\Composer\Plugin``.
3. The plugin exposes commands through
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.
4. After ``composer install`` or ``composer update``, the plugin runs
   ``vendor/bin/dev-tools dev-tools:sync``.
5. ``FastForward\DevTools\Console\Command\SyncCommand`` updates scripts, GitHub
   workflow stubs, ``.editorconfig``, ``dependabot.yml``, ``.gitignore``, and
   the wiki submodule in the consumer repository.
6. ``FastForward\DevTools\Console\Command\SkillsCommand`` synchronizes packaged skill
   links into the consumer ``.agents/skills`` directory.
7. ``FastForward\DevTools\Agent\Skills\SkillsSynchronizer`` creates missing
   links, repairs broken ones, and preserves consumer-owned directories.

Documentation Pipeline
----------------------

1. ``FastForward\DevTools\Console\Command\DocsCommand`` reads PSR-4 paths from
   ``composer.json`` via ``ComposerJsonInterface``.
2. It generates a temporary ``phpdocumentor.xml`` file in the configured cache
   directory.
3. phpDocumentor builds API pages from those PSR-4 paths.
4. phpDocumentor also builds the guide from the selected ``docs/`` source
   directory.
5. ``FastForward\DevTools\Console\Command\ReportsCommand`` combines that
   documentation build with PHPUnit coverage generation.

Dependency Injection
--------------------

Commands receive their dependencies through constructor injection provided by
``DevToolsServiceProvider``.

.. list-table::
   :header-rows: 1

   * - Interface
     - Purpose
   * - ``FastForward\DevTools\Process\ProcessBuilderInterface``
     - Builds process commands with a fluent API for arguments.
   * - ``FastForward\DevTools\Process\ProcessQueueInterface``
     - Queues and executes multiple processes in sequence.
   * - ``FastForward\DevTools\Filesystem\FilesystemInterface``
     - Abstracts filesystem operations.
   * - ``FastForward\DevTools\Composer\Json\ComposerJsonInterface``
     - Reads and validates ``composer.json`` metadata.
   * - ``Symfony\Component\Config\FileLocatorInterface``
     - Locates configuration files.
