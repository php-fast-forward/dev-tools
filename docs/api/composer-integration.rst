Composer Integration
====================

FastForward DevTools is exposed through both a Composer plugin and a dedicated
console application.

Startup Chain
-------------

1. ``bin/dev-tools`` loads ``bin/dev-tools.php``.
2. ``bin/dev-tools.php`` prefers the consumer project's
   ``vendor/autoload.php`` and falls back to the package autoloader.
3. ``bin/dev-tools.php`` starts ``FastForward\DevTools\DevTools`` and appends
   ``--no-plugins``.
4. ``FastForward\DevTools\DevTools`` sets ``standards`` as the default command
   and loads commands from
   ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``.

Composer Plugin Classes
-----------------------

.. list-table::
   :header-rows: 1

   * - Class
     - Purpose
   * - ``FastForward\DevTools\Composer\Plugin``
     - Registers the command provider and runs ``dev-tools:sync`` after
       Composer install and update.
   * - ``FastForward\DevTools\Composer\Capability\DevToolsCommandProvider``
     - Instantiates and returns the available command classes.
   * - ``FastForward\DevTools\DevTools``
     - Console application used by the local binary.

Why ``--no-plugins`` Is Appended
--------------------------------

The local binary already knows which commands it needs. Appending
``--no-plugins`` keeps the standalone application predictable and avoids
pulling unrelated Composer plugins into the command runtime.
