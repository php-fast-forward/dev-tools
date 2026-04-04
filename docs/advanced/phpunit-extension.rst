PHPUnit Extension
=================

The packaged ``phpunit.xml`` is intentionally opinionated. Besides enabling
strict PHPUnit flags, it registers
``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``.

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

Why This Helps Consumer Projects
--------------------------------

- tests can double final classes and final methods when the test environment
  needs it;
- developers get a quick desktop summary without reading the full terminal
  scrollback;
- event counts are available to the notification layer without adding ad-hoc
  test code.

What to Remember When Overriding ``phpunit.xml``
------------------------------------------------

If a consumer project replaces the packaged ``phpunit.xml``, it also replaces
this extension unless it re-registers it manually. That is usually fine, but
it explains why notifications or BypassFinals behavior may disappear after a
local override.

.. note::

   The notification subscriber still works when ``pcntl_fork()`` is
   unavailable. Forking only makes notification delivery less blocking on
   platforms that support it.
