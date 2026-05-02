PHPUnit Extension
=================

The packaged ``phpunit.xml`` is intentionally opinionated. Besides enabling
strict PHPUnit flags, it registers
``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension`` and
``Ergebnis\PHPUnit\AgentReporter\Extension``.

Runtime Chain
-------------

1. ``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``
   registers a tracer and two subscribers with PHPUnit.
2. ``FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber``
   enables ``DG\BypassFinals`` at suite start.
3. ``FastForward\DevTools\PhpUnit\Event\EventTracer`` records the events
   emitted during the run.
4. ``FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber``
   builds a summary notification and sends it when the run finishes.
5. ``Ergebnis\PHPUnit\AgentReporter\Extension`` replaces PHPUnit's normal
   output with a compact JSON report when an agent runtime is detected.
6. In structured DevTools runs, ``tests`` forces the same reporter path for
   the PHPUnit subprocess so the final nested payload remains deterministic
   even when the surrounding process would not naturally look agent-driven.

Why This Helps Consumer Projects
--------------------------------

- tests can double final classes and final methods when the test environment
  needs it;
- developers get a quick desktop summary without reading the full terminal
  scrollback;
- agent-driven runs consume far less terminal context while still keeping
  failure details and PHPUnit exit semantics intact;
- DevTools can preserve a single top-level JSON document while nesting the
  compact PHPUnit summary under ``context.output`` for bot-friendly parsing;
- event counts are available to the notification layer without adding ad-hoc
  test code.

What to Remember When Overriding ``phpunit.xml``
------------------------------------------------

If a consumer project replaces the packaged ``phpunit.xml``, it also replaces
these extensions unless it re-registers them manually. That is usually fine,
but it explains why notifications, BypassFinals behavior, or compact
agent-oriented output may disappear after a local override.

.. note::

   The notification subscriber still works when ``pcntl_fork()`` is
   unavailable. Forking only makes notification delivery less blocking on
   platforms that support it.
