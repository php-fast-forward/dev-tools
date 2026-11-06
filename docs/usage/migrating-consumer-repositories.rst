Migrating Consumer Repositories
===============================

Follow :doc:`dev-tools-2-migration`: publish and verify DevTools 2 first, then
prepare one migration PR per consumer. Pin runtime and skill sources, remove
the reverse Composer dependency, preserve customizations and verify required
workflow statuses before merge.

For new adoption use :doc:`../getting-started/installation`, preview
``dev-tools:sync`` and install skills through :doc:`syncing-packaged-skills`.
