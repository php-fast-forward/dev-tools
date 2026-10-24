Syncing Consumer Projects
=========================

``dev-tools:sync`` explicitly manages the remaining DevTools-owned assets:
Composer scripts/metadata, funding, CODEOWNERS, workflow callers, editor
defaults, ignore/export rules, license and Git hooks. Normal sync also generates
Wiki Markdown in ``.dev-tools/wiki-generated`` without creating a submodule.

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync --dry-run
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync --check
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync

Inspect generated changes. Copied workflows and existing Dependabot policy
remain preserved by default; replacement requires deliberate adoption.
Wiki generation is skipped in preview modes. ``skills`` and ``agents`` are
no longer phases. LLM Skills handles skills separately and does not synchronize
generic repository files. See :doc:`syncing-packaged-skills`.
