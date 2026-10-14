Syncing Consumer Projects
=========================

The ``dev-tools:sync`` command is the bridge between this repository and the
libraries that consume it.

For the focused skills-only workflow, see :doc:`syncing-packaged-skills`.
For packaged project agents, see :doc:`syncing-packaged-agents`.
For a first-time adoption path, see
:doc:`migrating-consumer-repositories`.

What the Command Changes
------------------------

.. list-table::
   :header-rows: 1

   * - Asset
     - Behavior
     - Overwrite policy
   * - ``composer.json`` scripts
     - Adds or updates ``dev-tools`` and ``dev-tools:fix``.
     - Updated in place.
   * - ``composer.json`` extra
     - Removes only deprecated DevTools-managed
       ``extra.grumphp.config-default-path`` values while preserving
       consumer-owned GrumPHP settings.
     - Updated in place.
   * - ``.github/workflows/*.yml``
     - Copies thin wrapper workflows from ``resources/github-actions`` that
       delegate to reusable workflows in ``php-fast-forward/dev-tools``.
     - Only when missing by default; replaceable with ``--overwrite``.
   * - ``.editorconfig``
     - Copies the packaged file from the repository root.
     - Only when missing by default; replaceable with ``--overwrite``.
   * - ``.github/dependabot.yml``
     - Copies the packaged Dependabot template.
     - Only when missing by default; replaceable with ``--overwrite``.
   * - ``.github/CODEOWNERS``
     - Generates managed ownership rules from local ``composer.json`` metadata,
       with commented suggestions when direct GitHub owners cannot be inferred.
     - Preserved by default; replaceable with ``--overwrite``.
   * - ``.agents/skills/<skill-name>``
     - Creates or repairs symlinks to packaged agent skills.
     - Creates missing links, repairs broken symlinks, and preserves existing
       non-symlink directories.
   * - ``.agents/agents/<agent-name>``
     - Creates or repairs symlinks to packaged project agents.
     - Creates missing links, repairs broken symlinks, and preserves existing
       non-symlink directories.
   * - ``.dev-tools/wiki-generated``
     - Generates local Markdown API documentation without creating a Git
       submodule.
     - Generated in normal mode; skipped in preview/check modes.
   * - ``.git/hooks/*``
     - Copies packaged hooks that prefer a local ``grumphp.yml`` override and
       otherwise use a project-relative reference to the active packaged
       DevTools ``grumphp.yml`` path resolved when sync installs them.
     - Replaced when drift is detected.

When to Run It
--------------

- right after installing the package with plugins disabled;
- after upgrading ``fast-forward/dev-tools`` and wanting new shared workflow
  stubs;
- when onboarding an older repository into the Fast Forward automation model.
- when packaged skills were added or updated and the consumer repository
  needs fresh links.

What It Needs
-------------

- a writable ``composer.json`` in the consumer project;
- permission to create local ``.github/`` files;
- permission to write local ``.dev-tools/wiki-generated`` documentation;
- permission to create local ``.agents/skills`` and ``.agents/agents``
  entries.

Workflow Wrapper Notes
----------------------

The synchronized workflow files in consumer repositories are wrappers, not the
full implementation. The actual reusable workflows live in the upstream
``php-fast-forward/dev-tools`` repository. When those reusable workflows need
local action implementations, they explicitly check out the upstream
``php-fast-forward/dev-tools`` repository into a dedicated
``.dev-tools-actions`` path in the run workspace first, so the consumer
repository does not need to mirror ``.github/actions`` locally. Workflows that
need only shared actions can use sparse checkout. The direct Wiki workflow
uses a full checkout pinned to a commit, installs the renderer there, and runs
it against the consumer project without changing its ``composer.json``,
``composer.lock``, or plugin allowlist.

That matters most for these cases:

*   ``.github/workflows/wiki.yml`` calls ``wiki-direct.yml`` for read-only
    generation on pull requests and direct publication from ``main`` to the
    separate Wiki repository. Only ``Home.md``, ``classes/``, and ``functions/``
    are generated surfaces; manually maintained pages outside them are
    preserved.
*   The packaged ``.github/workflows/wiki-maintenance.yml`` is a manual-only
    migration notice. Legacy reusable ``wiki-preview.yml`` and
    ``wiki-maintenance.yml`` remain available for unmigrated callers.
*   Normal sync generates ``.dev-tools/wiki-generated`` without ``--init``.
    The explicit ``wiki --init`` option remains available for legacy submodule
    users.
*   ``.github/workflows/auto-assign.yml`` and
    ``.github/workflows/changelog.yml`` accept a ``project`` input in the
    wrapper and also support the ``PROJECT`` repository variable.
*   The effective project-board contract is ``project`` first, then
    ``PROJECT``, then any reusable-workflow default that applies to
    ``php-fast-forward`` repositories.

.. important::

   Workflow stubs, ``.editorconfig``, and ``dependabot.yml`` are copied only
   when the target file does not already exist unless ``--overwrite`` is used.
   This protects consumer-specific customizations by default while still
   allowing explicit replacement during shared automation updates. The
   generated ``.github/CODEOWNERS`` file follows the same principle by keeping
   an existing file unless you explicitly request replacement. The ``skills``
   and ``agents`` phases follow the same spirit by preserving existing
   non-symlink directories inside ``.agents/skills`` and ``.agents/agents``.
