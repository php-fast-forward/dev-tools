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
- keeps PHPUnit result caching enabled by default;
- treats ``--cache`` as an explicit force-on flag and ``--no-cache`` as an
  explicit force-off flag for the current run;
- uses the selected workspace ``cache/phpunit`` directory only when caching
  stays enabled;
- skips the command with a warning when the repository has no default
  ``tests`` directory and no testable PHP source surface;
- can generate HTML coverage, Testdox, Clover, and raw PHP coverage output
  when ``--coverage`` is provided.

Useful Examples
---------------

.. code-block:: bash

   composer tests
   composer tests -- --filter=PluginTest
   composer tests --coverage=.dev-tools/coverage
   composer tests --no-cache --bootstrap=tests/bootstrap.php
   composer tests --json
   composer tests --pretty-json

Coverage Outputs
----------------

When ``--coverage=.dev-tools/coverage`` is used, PHPUnit writes:

- ``.dev-tools/coverage/index.html``
- ``.dev-tools/coverage/testdox.html``
- ``.dev-tools/coverage/clover.xml``
- ``.dev-tools/coverage/coverage.php``

Built-In PHPUnit Extensions
---------------------------

The packaged ``phpunit.xml`` registers:

- ``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``, which
  wires together:

  - ``FastForward\DevTools\PhpUnit\Event\TestSuite\ByPassfinalsStartedSubscriber``,
    which enables ``DG\BypassFinals`` when the suite starts;
  - ``FastForward\DevTools\PhpUnit\Event\EventTracer``, which records PHPUnit
    events in memory;
  - ``FastForward\DevTools\PhpUnit\Event\TestSuite\JoliNotifExecutionFinishedSubscriber``,
    which sends a desktop notification after the run when the local platform
    supports it.
- ``Ergebnis\PHPUnit\AgentReporter\Extension``, which replaces PHPUnit's
  default verbose terminal output with a compact JSON summary when an agent
  runtime is detected.

When ``tests`` itself runs in structured mode, DevTools also forces the
reporter path for the PHPUnit subprocess so the nested payload remains compact
and parseable for bots and agent workflows.

Desktop notification delivery is synchronous in the PHPUnit process. The
subscriber does not fork a second reporting/shutdown lifecycle. Transport
exceptions remain visible through PHPUnit's subscriber warnings and configured
warning/exit policies. Packaged extension registration and default profiles
remain unchanged; this delivery change introduces no notification opt-in policy.

Programmatic Coverage Access
-----------------------------

The ``CoverageSummaryLoader`` class provides programmatic access to coverage
data. This is useful when you need to integrate coverage metrics into
external tooling or build custom reports:

.. code-block:: php

   use FastForward\DevTools\PhpUnit\Coverage\CoverageSummaryLoader;

   $loader = new CoverageSummaryLoader();
   $summary = $loader->load('.dev-tools/coverage/coverage.php');

   $summary->executedLines();      // e.g., 142
   $summary->executableLines();   // e.g., 168
   $summary->percentage();        // e.g., 84.52
   $summary->percentageAsString(); // e.g., "84.52%"

``FastForward\DevTools\PhpUnit\Coverage\MinimumCoverageResolver`` is a final
readonly invocable validator with signature ``__invoke(?string $value): ?float``.
It trims the supplied string and returns ``null`` for null or blank input,
disabling the threshold. Numeric percentages from ``0`` through ``100``
inclusive return a float. Invalid values throw ``InvalidArgumentException``
with the existing messages:

- ``The --min-coverage option MUST be a numeric percentage.`` for non-numeric input;
- ``The --min-coverage option MUST be between 0 and 100.`` for an out-of-range percentage.

The validator reads no environment or console state. ``TestsCommand`` still
selects the CLI value first, reads ``FAST_FORWARD_MIN_COVERAGE`` only when
that value is null, and passes the selected value to this validator.

Programmatic Bootstrap and Result Interpretation
------------------------------------------------

``FastForward\DevTools\PhpUnit\Bootstrap\BootstrapShimGenerator`` accepts
``generate($projectBootstrap, $cacheDirectory, $runtimeAutoload = null)``.
It writes and returns a bootstrap shim under the cache directory. The shim
loads the project bootstrap first, then the selected runtime autoloader if that
file exists. Pass an explicit isolated runtime autoloader when the caller owns
that runtime; omitting it retains the current DevTools runtime resolution and
the existing consumer-first loading order.

``FastForward\DevTools\PhpUnit\Result\ProcessResultInterpreter`` is an
invocable, final readonly helper that interprets captured process output
without starting a process, reading files or writing console output:

.. code-block:: php

   use FastForward\DevTools\PhpUnit\Result\ProcessResultInterpreter;

   $interpret = new ProcessResultInterpreter();
   $payload = $interpret($capturedOutput, $exitCode);

The helper retains the reporter payload shape. A nonzero process exit code or
an explicit reporter ``result`` of ``failure`` produces ``failure``; exit code
``0`` does not override a reporter failure. Multiple complete reporter objects
also produce ``failure``, even when each reports success, and preserve the full
trimmed captured output as ``raw_output``. ``TestsCommand`` propagates a
structured failure as command failure even when the process queue exits ``0``.

Decoded fields from a single reporter payload are retained. Human-readable or
malformed output keeps the existing trimmed ``raw_output`` fallback; when a
recognized final reporter object follows supplemental text, that text is
preserved there instead. Empty output returns only the exit-code-derived
result. Recovery from malformed output has a bounded scan budget. Exhausting
that budget returns failure with the complete captured output, even when an
ambiguous stream ends with a success object. This helper does not define a
separate versioned result schema.

When to Override Locally
------------------------

Create your own ``phpunit.xml`` in the consumer project when you need a
different bootstrap file, extra extensions, or alternative strictness flags.
DevTools will prefer the local file automatically, but consumer projects that
replace the packaged configuration must re-register both bundled extensions if
they still want desktop notifications, BypassFinals support, and compact
agent-oriented JSON output.

.. note::

   Notification transport errors are not silently ignored. PHPUnit's subscriber
   warning and exit policies determine their effect on the completed test run.
