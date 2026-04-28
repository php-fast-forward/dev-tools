self-update
===========

``self-update`` updates the installed ``fast-forward/dev-tools`` package
through Composer.

Usage
-----

.. code-block:: bash

   vendor/bin/dev-tools self-update
   vendor/bin/dev-tools self-update --global
   composer dev-tools:self-update

Options
-------

.. list-table::
   :header-rows: 1
   :widths: 24 76

   * - Option
     - Description
   * - ``--global``
     - Run ``composer global update fast-forward/dev-tools`` instead of
       updating the current project installation.

Global runtime options
----------------------

The standalone DevTools binary also accepts Composer-like global runtime
options before the command name:

.. code-block:: bash

   vendor/bin/dev-tools --working-dir=/path/to/project tests
   vendor/bin/dev-tools --auto-update tests

``--working-dir`` (or ``-d``) switches the process directory before resolving
paths, managed files, or command defaults. This lets a globally installed
binary operate on another project without first changing shell directories.
Composer executions can use Composer's own ``--working-dir``/``-d`` option.

``--auto-update`` runs the project self-update flow before the requested
command. The same behavior MAY be enabled with ``FAST_FORWARD_AUTO_UPDATE``;
set it to ``global`` when the update should target Composer's global
installation. Auto-update failures are reported as warnings and do not block
the requested command.

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
