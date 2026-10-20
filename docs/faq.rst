FAQ
===

How can I use DevTools without require-dev?
------------------------------------------

Use CPX with ``--skip-local`` and an exact published version. Follow
:doc:`getting-started/installation` and :doc:`usage/dev-tools-2-migration`.

Does installation synchronize repository files?
----------------------------------------------

No. Version 2 removes Composer plugin activation and install/update hooks.
Run ``dev-tools:sync`` explicitly after reviewing its preview.

How do I install skills and role prompts?
----------------------------------------

LLM Skills installs the ``fast-forward-*`` procedural packages independently.
See :doc:`usage/syncing-packaged-skills`. Custom role prompts remain
repository-owned; ``skills`` and ``agents`` are removed from DevTools.

Does code-style update dependencies?
-----------------------------------

No. Code-style runs Composer Normalize as a plugin from the isolated runtime
root against the absolute consumer manifest with ``--no-update-lock``, then
runs ECS. It does not run ``composer update --lock``. Use ``--fix`` to permit
formatting changes; no standalone Composer Normalize binary is assumed.

Where did ``.docheader`` come from?
-----------------------------------

``FastForward\DevTools\Console\Command\PhpDocCommand`` creates it automatically when it
is missing. The template comes from the packaged file and is rewritten with the
current package name when possible.

How do I run only one test class or method?
-------------------------------------------

Use the ``tests`` command with ``--filter``:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests --filter=SyncCommandTest

Why can my tests double final classes?
--------------------------------------

The packaged PHPUnit extension enables ``DG\BypassFinals`` at suite start
through ``ByPassfinalsStartedSubscriber``.

Why did desktop notifications stop appearing after I customized PHPUnit?
------------------------------------------------------------------------

If you replaced the packaged ``phpunit.xml``, you may also have removed
``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension`` and
``Ergebnis\PHPUnit\AgentReporter\Extension``. Re-add them if you want the
notification behavior, BypassFinals support, and compact agent-oriented output
back.

Is ``AddMissingClassPhpDocRector`` enabled by default?
------------------------------------------------------

No. The class is shipped and tested, but the packaged ``rector.php`` enables
only ``AddMissingMethodPhpDocRector`` and ``RemoveEmptyDocBlockRector``.

What happens if ``.github/wiki`` already exists?
------------------------------------------------

``dev-tools:sync`` leaves it alone and generates local API Markdown in
``.dev-tools/wiki-generated`` instead. Normal sync does not create or update a
Wiki submodule. When migrating to direct Wiki publication, remove the obsolete
gitlink and matching ``.gitmodules`` stanza explicitly, preserving the separate
Wiki repository and any other submodules. ``wiki --init`` remains available as
an explicit legacy option.

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
