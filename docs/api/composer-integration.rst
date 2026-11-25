Standalone Runtime and Composer Scripts
=======================================

DevTools is a Composer-distributed library exposing ``bin/dev-tools``.
Version 2 removes the plugin, command provider and proxy commands.
Installing or updating it does not synchronize a repository.

The binary loads its own installation's autoloader. The shared container and
``DevToolsCommandLoader`` lazily resolve Symfony Console commands. Consumer
autoloading supports tools executing project code; it does not select the CLI.

Use CPX with ``--skip-local`` and an exact package version. Reviewed Composer
scripts can wrap that invocation without requiring DevTools. The command
``runtime --json --no-ansi --no-interaction`` reports ``binary``, ``autoload``,
``package_root``, ``project_root`` and ``version`` for automation.
See :doc:`../usage/dev-tools-2-migration`.
