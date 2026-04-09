Overriding Defaults
==================

Local override files let a consumer project keep the Fast Forward baseline
without forking the whole package.

Resolution Order
----------------

``FastForward\DevTools\Command\AbstractCommand::getConfigFile()`` resolves
configuration in this order:

1. Check whether the file exists in the current working directory.
2. Use the local file when it exists.
3. Otherwise fall back to the packaged file inside ``fast-forward/dev-tools``.

Commands and Their Configuration Files
--------------------------------------

.. list-table::
   :header-rows: 1

   * - Command
     - Local file
     - Fallback behavior
   * - ``code-style``
     - ``ecs.php``
     - Falls back to the packaged ECS configuration.
   * - ``refactor``
     - ``rector.php``
     - Falls back to the packaged Rector configuration.
   * - ``tests``
     - ``phpunit.xml``
     - Falls back to the packaged PHPUnit configuration.
   * - ``phpdoc``
     - ``.php-cs-fixer.dist.php`` and ``rector.php``
     - Falls back to the packaged files; ``.docheader`` is created locally
       when missing.
   * - ``docs``
     - ``docs/`` or another path passed with ``--source``
     - The selected guide source must exist locally.
   * - ``dev-tools:sync``
     - Consumer repository files
     - Works directly against local project files such as ``composer.json`` and
       ``.github/*``.

A Practical Example
-------------------

To customize Rector for one library, create ``rector.php`` in the consumer
project root. The ``refactor`` command and the Rector phase inside ``phpdoc``
will use that file instead of the packaged default.

Extending ECS Configuration
----------------------------

Instead of copying the entire ``ecs.php`` file, consumers can extend the
default configuration using the ``ECSConfig`` class:

.. code-block:: php

   <?php

   use FastForward\DevTools\Config\ECSConfig;
   use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;

   $config = ECSConfig::configure();
   $config->withRules([CustomRule::class]);
   $config->withConfiguredRule(PhpdocAlignFixer::class, ['align' => 'right']);

   return $config;

Extending Rector Configuration
-------------------------------

Instead of copying the entire ``rector.php`` file, consumers can extend the
default configuration using the ``RectorConfig`` class:

.. code-block:: php

   <?php

   use FastForward\DevTools\Config\RectorConfig;

   return RectorConfig::configure(
       static function (\Rector\Config\RectorConfig $rectorConfig): void {
           $rectorConfig->rules([
               // custom rules
           ]);

           $rectorConfig->skip([
               // custom skips
           ]);
       }
   );

This approach:

- Eliminates duplication of the base configuration
- Automatically receives upstream updates
- Only requires overriding what is needed

What Is Not Overwritten Automatically
--------------------------------------

- existing workflow files in ``.github/workflows/``;
- an existing ``.editorconfig``;
- an existing ``.github/dependabot.yml``;
- an existing ``.github/wiki`` directory or submodule.

.. tip::

   Start with the packaged defaults, copy only the file you need to customize,
   and keep the rest on the shared baseline. That gives you the least
   maintenance overhead across Fast Forward libraries.
