Quickstart
==========

Follow :doc:`installation` to select an exact published runtime version.

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards

Use ``--fix`` to allow quality tools to modify files, ``--json`` for structured
results and ``--working-dir`` to select a project. Create ``docs/index.rst``
before building guides. Preview ``dev-tools:sync --dry-run`` and review the
diff before applying synchronization. Skills are installed separately through
:doc:`../usage/syncing-packaged-skills`; version 1 consumers should follow
:doc:`../usage/dev-tools-2-migration`.
