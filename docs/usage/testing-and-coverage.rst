Testing and Coverage
====================

The ``tests`` command is the fastest way to reuse the package's PHPUnit
defaults while still allowing local overrides.

Default Behavior
----------------

When you run ``tests``, DevTools:

- resolves ``phpunit.xml`` from the consumer root first and falls back to the
  packaged default;
- uses ``vendor/autoload.php`` as the default bootstrap file;
- stores cache data in ``tmp/cache/phpunit`` unless ``--no-cache`` is used;
- can generate HTML coverage, Testdox, Clover, and raw PHP coverage output
  when ``--coverage`` is provided.

Useful Examples
---------------

.. code-block:: bash

   composer tests
   composer tests -- --filter=PluginTest
   vendor/bin/dev-tools tests --coverage=build/coverage
   vendor/bin/dev-tools tests --no-cache --bootstrap=tests/bootstrap.php

Coverage Outputs
----------------

When ``--coverage=build/coverage`` is used, PHPUnit writes:

- ``build/coverage/index.html``
- ``build/coverage/testdox.html``
- ``build/coverage/clover.xml``
- ``build/coverage/coverage.php``

Built-In PHPUnit Extension
--------------------------

The packaged ``phpunit.xml`` registers
``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``.

That extension wires together:

- ``FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber``,
  which enables ``DG\BypassFinals`` when the suite starts;
- ``FastForward\DevTools\PhpUnit\Event\EventTracer``, which records PHPUnit
  events in memory;
- ``FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber``,
  which sends a desktop notification after the run when the local platform
  supports it.

Programmatic Coverage Access
-----------------------------

The ``CoverageSummaryLoader`` class provides programmatic access to coverage
data. This is useful when you need to integrate coverage metrics into
external tooling or build custom reports:

.. code-block:: php

   use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;

   $loader = new CoverageSummaryLoader();
   $summary = $loader->load('build/coverage/coverage.php');

   $summary->executedLines();      // e.g., 142
   $summary->executableLines();   // e.g., 168
   $summary->percentage();        // e.g., 84.52
   $summary->percentageAsString(); // e.g., "84.52%"

When to Override Locally
------------------------

Create your own ``phpunit.xml`` in the consumer project when you need a
different bootstrap file, extra extensions, or alternative strictness flags.
DevTools will prefer the local file automatically.

.. note::

   Desktop notifications are a convenience feature, not a requirement. Test
   execution still works when the notification transport is unavailable.
