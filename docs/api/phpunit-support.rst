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

These classes are especially relevant when a consumer project overrides the
packaged ``phpunit.xml`` and wants to preserve the same runtime behavior.
