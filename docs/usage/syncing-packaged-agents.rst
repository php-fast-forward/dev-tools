Syncing Packaged Agents
=======================

The ``agents`` command keeps the consumer repository's ``.agents/agents``
directory aligned with the packaged project-agent prompts shipped inside
``fast-forward/dev-tools``.

Why This Command Exists
-----------------------

Fast Forward libraries can share role-based project agents without copying
prompt files into every consumer repository. The packaged agent directories
live in this repository, while consumer repositories receive lightweight
symlinks that point back to the packaged source.

That approach keeps upgrades simple:

- updating ``fast-forward/dev-tools`` changes the packaged project-agent source;
- rerunning ``agents`` repairs missing or broken links;
- consumer-specific directories are preserved when they are not symlinks.

How to Run It
-------------

.. code-block:: bash

   composer agents
   vendor/bin/dev-tools agents

What the Command Does
---------------------

.. list-table::
   :header-rows: 1

   * - Situation
     - Behavior
   * - ``.agents/agents`` is missing
     - Creates the directory in the consumer repository.
   * - A packaged agent is missing locally
     - Creates a symlink that points to the packaged agent directory.
   * - A valid symlink already exists
     - Leaves the link unchanged.
   * - A symlink is broken
     - Removes it and recreates it with the current packaged target.
   * - A real directory already exists at the target path
     - Preserves the directory and skips link creation to avoid overwriting
       consumer-owned content.

When to Run It Manually
-----------------------

Run ``agents`` explicitly when:

- you upgraded ``fast-forward/dev-tools`` and want to refresh local
  project-agent links;
- someone deleted or broke entries inside ``.agents/agents``;
- Composer plugins were disabled during install, so ``dev-tools:sync`` did not
  run automatically;
- you are iterating on packaged project agents and want to verify the
  consumer-facing links without rerunning the entire repository sync flow.

Relationship with ``dev-tools:sync``
------------------------------------

``dev-tools:sync`` runs ``skills`` and ``agents`` in normal mode. That means
the full onboarding command refreshes workflow stubs, repository defaults,
packaged skills, and packaged role prompts in one pass.

What the Command Does Not Overwrite
-----------------------------------

The command does not replace an existing non-symlink directory inside
``.agents/agents``. This protects local experiments, package-specific custom
agents, or directories managed by another tool.
