Architecture and Command Lifecycle
==================================

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
5. Individual commands resolve local configuration first and packaged
   fallbacks second through
   ``FastForward\DevTools\Command\AbstractCommand::getConfigFile()``.

Consumer Synchronization Lifecycle
----------------------------------

1. A library installs ``fast-forward/dev-tools`` as a development dependency.
2. Composer loads ``FastForward\DevTools\Composer\Plugin``.
3. The plugin exposes commands through
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.
4. After ``composer install`` or ``composer update``, the plugin runs
   ``vendor/bin/dev-tools dev-tools:sync``.
5. ``FastForward\DevTools\Command\SyncCommand`` updates scripts, GitHub
   workflow stubs, ``.editorconfig``, ``dependabot.yml``, ``.gitignore``, and
   the wiki submodule in the consumer repository.
6. ``FastForward\DevTools\Command\SkillsCommand`` synchronizes packaged skill
   links into the consumer ``.agents/skills`` directory.
7. ``FastForward\DevTools\Agent\Skills\SkillsSynchronizer`` creates missing
   links, repairs broken ones, and preserves consumer-owned directories.

Documentation Pipeline
----------------------

1. ``FastForward\DevTools\Command\DocsCommand`` reads PSR-4 paths from
   ``composer.json``.
2. It generates a temporary ``phpdocumentor.xml`` file in
   ``tmp/cache/phpdoc``.
3. phpDocumentor builds API pages from those PSR-4 paths.
4. phpDocumentor also builds the guide from the selected ``docs/`` source
   directory.
5. ``FastForward\DevTools\Command\ReportsCommand`` combines that
   documentation build with PHPUnit coverage generation.

Key Abstraction
---------------

``FastForward\DevTools\Command\AbstractCommand`` is the main shared layer. It
centralizes:

- current working directory detection;
- absolute path resolution;
- local-versus-packaged config lookup;
- command-to-command dispatch inside the same application;
- access to Composer package metadata such as PSR-4 namespaces and project
  description.
