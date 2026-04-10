Syncing Consumer Projects
=========================

The ``dev-tools:sync`` command is the bridge between this repository and the
libraries that consume it.

For the focused skills-only workflow, see :doc:`syncing-packaged-skills`.

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
     - Sets ``extra.grumphp.config-default-path``.
     - Updated in place.
   * - ``.github/workflows/*.yml``
     - Copies stub workflows from ``resources/github-actions``.
     - Only when missing.
   * - ``.editorconfig``
     - Copies the packaged file from the repository root.
     - Only when missing.
   * - ``.github/dependabot.yml``
     - Copies the packaged Dependabot template.
     - Only when missing.
   * - ``.agents/skills/<skill-name>``
     - Creates or repairs symlinks to packaged agent skills.
     - Creates missing links, repairs broken symlinks, and preserves existing
       non-symlink directories.
   * - ``.github/wiki``
     - Adds a Git submodule derived from ``git remote origin``.
     - Only when missing.

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
- a configured ``git remote origin`` if the wiki submodule must be created;
- permission to create local ``.github/`` files;
- permission to create local ``.agents/skills`` entries.

.. important::

   Workflow stubs, ``.editorconfig``, and ``dependabot.yml`` are copied only
   when the target file does not already exist. This protects
   consumer-specific customizations. The ``skills`` phase follows the same
   spirit by preserving existing non-symlink directories inside
   ``.agents/skills``.
