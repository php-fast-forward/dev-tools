Architecture and Command Lifecycle
=================================

The package has two connected execution models: local command execution and
consumer repository synchronization.

Local Command Lifecycle
-----------------------

1. ``bin/dev-tools`` loads ``bin/dev-tools.php``.
2. ``bin/dev-tools.php`` prefers the consumer ``vendor/autoload.php`` and
   falls back to the package autoloader.
3. ``FastForward\DevTools\Console\DevTools::create()`` builds a shared
   container from ``FastForward\DevTools\ServiceProvider\DevToolsServiceProvider``.
4. ``FastForward\DevTools\Console\CommandLoader\DevToolsCommandLoader``
   lazily discovers ``#[AsCommand]`` classes and resolves them from that
   container.
5. ``standards`` is used as the default command when no explicit command name
   is given.
6. ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider`` only
   adapts the same application command set for Composer plugin integration.

Consumer Synchronization Lifecycle
----------------------------------

1. A library installs ``fast-forward/dev-tools`` as a development dependency.
2. Composer loads ``FastForward\DevTools\Composer\Plugin``.
3. The plugin exposes commands through
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.
4. After ``composer install`` or ``composer update``, the plugin runs
   ``vendor/bin/dev-tools dev-tools:sync``.
5. ``FastForward\DevTools\Console\Command\SyncCommand`` updates
   ``composer.json`` scripts, funding metadata, workflow stubs,
   ``.editorconfig``, ``dependabot.yml``, ``.gitignore``,
   ``.gitattributes``, the project license, and packaged Git hooks.
6. In normal mode, ``dev-tools:sync`` also runs ``wiki --init``,
   ``skills``, and ``agents`` to initialize the wiki submodule and
   synchronize packaged links into ``.agents/skills`` and
   ``.agents/agents``.
7. In ``--dry-run``, ``--check``, and ``--interactive`` modes, ``wiki``,
   ``skills``, and ``agents`` are skipped because they do not yet expose
   non-destructive verification paths.
8. ``FastForward\DevTools\Sync\PackagedDirectorySynchronizer`` creates
   missing links, repairs broken ones, and preserves consumer-owned
   directories for both synchronization flows.

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

``DevToolsServiceProvider`` builds the shared application container used by
``DevTools::create()``. Most commands receive collaborators through
constructor injection once resolved by that container, while command discovery
itself stays lazy through ``DevToolsCommandLoader``.

The provider wires the command runtime by concern rather than by one flat
command list:

.. list-table::
   :header-rows: 1

   * - Concern
     - Services
   * - ``FastForward\DevTools\Process\ProcessBuilderInterface``
     - ``ProcessBuilderInterface`` and ``ProcessQueueInterface`` build and
       execute subprocess pipelines, while process environment and output
       Symfony-style sections keep nested command output readable without PTY
       and suppress unnecessary Xdebug overhead in child processes.
   * - ``Filesystem and metadata``
     - ``FilesystemInterface``, ``ComposerJsonInterface``, and
       ``FileLocatorInterface`` resolve local files, project metadata, and
       packaged resources.
   * - ``Console bootstrapping``
     - ``CommandLoaderInterface`` resolves lazy command loading, and
       ``Composer\Plugin\Capability\CommandProvider`` exposes the same
       command set to Composer.
   * - ``Changelog and Git``
     - The changelog manager, parser, renderer, checker, and
       ``GitClientInterface`` support changelog authoring, verification, and
       release-note flows.
   * - ``Synchronization helpers``
     - Git ignore, Git attributes, license, resource diffing, and coverage
       summary services support consumer sync and reporting workflows.
   * - ``Shared infrastructure``
     - ``LoggerInterface``, ``ClockInterface``,
       ``RuntimeEnvironmentInterface``, and Twig's ``LoaderInterface``
       provide reusable runtime infrastructure, including centralized checks
       for GitHub Actions, generic CI, Composer test runs, and truthy
       environment flags.
