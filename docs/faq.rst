FAQ
===

Why does ``composer dev-tools docs`` fail in a new package?
-----------------------------------------------------------

Because the command requires a ``docs/`` directory. Create at least
``docs/index.rst`` before running ``docs`` or ``reports``.

Why did my ``composer.json`` change after installing the package?
-----------------------------------------------------------------

The Composer plugin runs ``dev-tools:sync`` after install and update.
That command adds the ``dev-tools`` scripts and updates
``extra.grumphp.config-default-path`` in the consumer project.

Do I always need to run ``dev-tools:sync`` manually?
----------------------------------------------------

Usually no. The plugin already runs it after ``composer install`` and
``composer update``. Manual sync is most useful when plugins were disabled or
after upgrading ``fast-forward/dev-tools`` and wanting to refresh consumer
automation. That flow also runs ``skills`` and ``agents`` so packaged skill
links and packaged project-agent links are kept up to date.

When should I run ``composer dev-tools skills`` manually?
---------------------------------------------------------

Run it when you want to refresh ``.agents/skills`` without rerunning the full
consumer sync flow, especially after upgrading ``fast-forward/dev-tools`` or
after deleting a packaged skill link locally.

When should I run ``composer agents`` manually?
-----------------------------------------------

Run it when you want to refresh ``.agents/agents`` without rerunning the full
consumer sync flow, especially after upgrading ``fast-forward/dev-tools`` or
after deleting a packaged project-agent link locally.

Why does ``code-style`` touch ``composer.lock``?
------------------------------------------------

Because ``FastForward\DevTools\Console\Command\CodeStyleCommand`` always runs
``composer update --lock --quiet`` before Composer Normalize and ECS.

Where did ``.docheader`` come from?
-----------------------------------

``FastForward\DevTools\Console\Command\PhpDocCommand`` creates it automatically when it
is missing. The template comes from the packaged file and is rewritten with the
current package name when possible.

How do I run only one test class or method?
-------------------------------------------

Use the ``tests`` command with ``--filter``:

.. code-block:: bash

   composer dev-tools tests -- --filter=SyncCommandTest

Why can my tests double final classes?
--------------------------------------

The packaged PHPUnit extension enables ``DG\BypassFinals`` at suite start
through ``ByPassfinalsStartedSubscriber``.

Why did desktop notifications stop appearing after I customized PHPUnit?
------------------------------------------------------------------------

If you replaced the packaged ``phpunit.xml``, you may also have removed
``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``. Re-add the
extension if you want the notification behavior back.

Is ``AddMissingClassPhpDocRector`` enabled by default?
------------------------------------------------------

No. The class is shipped and tested, but the packaged ``rector.php`` enables
only ``AddMissingMethodPhpDocRector`` and ``RemoveEmptyDocBlockRector``.

What happens if ``.github/wiki`` already exists?
------------------------------------------------

``dev-tools:sync`` leaves it alone. The wiki submodule is only created when the
directory is missing.

What happens if ``.agents/skills/my-skill`` already exists?
-----------------------------------------------------------

If that path is a real directory instead of a symlink, the ``skills`` command
preserves it and skips link creation. Broken symlinks are repaired, but
consumer-owned directories are not overwritten automatically.

What happens if ``.agents/agents/my-agent`` already exists?
-----------------------------------------------------------

If that path is a real directory instead of a symlink, the ``agents`` command
preserves it and skips link creation. Broken symlinks are repaired, but
consumer-owned directories are not overwritten automatically.

How do I override only one tool without forking the whole package?
------------------------------------------------------------------

Create only the local configuration file you want to customize, such as
``rector.php`` or ``phpunit.xml``. DevTools will prefer that file and keep the
rest on the packaged defaults.

How do I extend the ECS configuration without copying the whole file?
-----------------------------------------------------------------------

Use the ``ECSConfig`` class to extend instead of replace:

.. code-block:: php

   <?php

   use FastForward\DevTools\Config\ECSConfig;

   $config = ECSConfig::configure();
   $config->withRules([CustomRule::class]);

   return $config;

This approach automatically receives upstream updates while allowing additive customization.

How do I extend the Rector configuration without copying the whole file?
-------------------------------------------------------------------------

Use the ``RectorConfig`` class to extend instead of replace:

.. code-block:: php

   <?php

   use FastForward\DevTools\Config\RectorConfig;

   return RectorConfig::configure(
       static function (\Rector\Config\RectorConfig $rectorConfig): void {
           $rectorConfig->rules([CustomRule::class]);
       }
   );

This approach automatically receives upstream updates while allowing additive customization.

How do I extend the dependency analyser configuration without copying the whole file?
-------------------------------------------------------------------------------------

Use the ``ComposerDependencyAnalyserConfig`` class to extend instead of replace:

.. code-block:: php

   <?php

   use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
   use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
   use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

   return ComposerDependencyAnalyserConfig::configure(
       static function (Configuration $configuration): void {
           $configuration->ignoreErrorsOnPackage(
               'vendor/package',
               [ErrorType::UNUSED_DEPENDENCY],
           );
       }
   );

This keeps the packaged baseline while allowing project-specific analyser ignores.

Can I generate coverage without running the full ``standards`` pipeline?
------------------------------------------------------------------------

Yes. Run ``vendor/bin/dev-tools tests --coverage=.dev-tools/coverage`` to generate
coverage directly.
