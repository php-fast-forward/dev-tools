self-update
===========

``self-update`` updates the installed ``fast-forward/dev-tools`` package
through Composer.

Usage
-----

.. code-block:: bash

   vendor/bin/dev-tools self-update
   composer dev-tools:self-update

When the standalone ``dev-tools`` binary is itself loaded from Composer's
global installation, ``self-update`` automatically targets
``composer global update fast-forward/dev-tools``. Local project
installations update the current project by default.

Global runtime options
----------------------

The standalone DevTools binary also accepts Composer-like global runtime
options before the command name:

.. code-block:: bash

   vendor/bin/dev-tools --working-dir=/path/to/project tests
   vendor/bin/dev-tools --workspace-dir=.artifacts reports
   vendor/bin/dev-tools --auto-update tests

``--working-dir`` (or ``-d``) switches the process directory before resolving
paths, managed files, or command defaults. This lets a globally installed
binary operate on another project without first changing shell directories.
Composer executions can use Composer's own ``--working-dir``/``-d`` option.

``--workspace-dir`` (or ``-w``) changes where generated DevTools artifacts and
caches are written when command-specific paths are omitted. It does not change
the project root selected by ``--working-dir``. Composer plugin executions can use
``FAST_FORWARD_WORKSPACE_DIR=.artifacts`` to apply the same workspace policy.
Explicit command options such as ``--target``, ``--coverage``, ``--metrics``,
and ``--cache-dir`` continue to take precedence over the workspace default.

``--auto-update`` runs the self-update flow before the requested command. The
same behavior MAY be enabled with ``FAST_FORWARD_AUTO_UPDATE=1``. When the
active ``dev-tools`` binary is already installed globally, auto-update also
targets the global installation by default. Auto-update failures are reported
as warnings and do not block the requested command.

Version freshness check
-----------------------

When DevTools runs from an installed package, the binary checks Composer
metadata for the latest stable ``fast-forward/dev-tools`` release. If a newer
stable version is available, DevTools prints a warning recommending
``dev-tools self-update``. This check is best-effort: network, Composer, or
metadata failures are ignored so the requested command can continue normally.
The check is skipped automatically in CI environments, including GitHub
Actions, so freshly installed consumer workflows do not spend time querying
release metadata. Set ``FAST_FORWARD_SKIP_VERSION_CHECK=1`` to disable the
warning in other non-interactive contexts.
