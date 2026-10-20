Installation
============

DevTools 2 is a standalone CLI. CPX keeps its runtime outside the consumer's
Composer dependency graph. Install CPX following https://github.com/laravel/cpx
and select PHP compatible with the chosen DevTools release and bundled tools.
Install the consumer's own dependencies before executing tests.

Assign an exact published 2.x numeric version to ``DEV_TOOLS_VERSION``. This
variable does not assert that a particular release is already published.

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" list
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests

Run from the consumer root or use ``--working-dir``. ``--skip-local`` avoids
a project-local installation. No Composer commands or automatic install/update
hooks are registered. Repository setup is explicit:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync --dry-run
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync

Install skills separately through :doc:`../usage/syncing-packaged-skills`.
For DevTools development itself use ``composer install`` followed by
``php bin/dev-tools tests`` or the root ``composer dev-tools`` script.
