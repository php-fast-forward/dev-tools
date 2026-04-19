PHPUnit Support Classes
=======================

The packaged test configuration includes a small integration layer under
``FastForward\DevTools\PhpUnit``.

.. list-table::
   :header-rows: 1

   * - Class
     - Role
     - Notes
   * - ``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``
     - Registers tracer and subscribers
     - Wired through ``phpunit.xml``.
   * - ``FastForward\DevTools\PhpUnit\Event\EventTracer``
     - Stores PHPUnit events by class name
     - Used to build notification summaries.
   * - ``FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber``
     - Enables ``DG\BypassFinals``
     - Allows tests to work with final constructs.
   * - ``FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber``
     - Sends desktop notifications
     - Summarizes pass, failure, error, runtime, and memory data.
   * - ``FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoaderInterface``
     - Loads PHPUnit coverage reports
     - Contract for loading serialized PHP coverage data.
   * - ``FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader``
     - Loads PHPUnit coverage reports
     - Implementation that reads ``coverage-php`` output.
   * - ``FastForward\DevTools\PhpUnit\Coverage\CoverageSummary``
     - Represents line coverage metrics
     - Provides executed lines, total executable lines, and percentage calculations.

Coverage Report Loading
-----------------------

DevTools provides a reusable layer for loading PHPUnit's serialized
``coverage-php`` output. This is useful when you need to extract line
coverage metrics programmatically.

``CoverageSummaryLoaderInterface`` defines the contract:

.. code-block:: php

   namespace FastForward\DevTools\PhpUnit\Coverage;

   interface CoverageSummaryLoaderInterface
   {
       public function load(string $coverageReportPath): CoverageSummary;
   }

``CoverageSummaryLoader`` implements this contract:

.. code-block:: php

   use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;

   $loader = new CoverageSummaryLoader();
   $summary = $loader->load('build/coverage/coverage.php');

   $summary->executedLines();      // Number of covered lines
   $summary->executableLines();   // Total number of executable lines
   $summary->percentage();        // Coverage as float (0-100)
   $summary->percentageAsString(); // Formatted string like "85.50%"

.. note::

   The loader expects the PHP file produced by PHPUnit's ``--coverage-php`` option.
   It must contain a ``SebastianBergmann\CodeCoverage\CodeCoverage`` instance.

These classes are especially relevant when a consumer project overrides the
packaged ``phpunit.xml`` and wants to preserve the same runtime behavior.
