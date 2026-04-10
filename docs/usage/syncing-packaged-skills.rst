Syncing Packaged Skills
=======================

The ``skills`` command keeps the consumer repository's ``.agents/skills``
directory aligned with the skills shipped inside
``fast-forward/dev-tools``.

Why This Command Exists
-----------------------

Fast Forward libraries can share agent skills without copying them into every
consumer repository. The packaged skill directories live in this repository,
while consumer repositories receive lightweight symlinks that point back to the
packaged source.

That approach keeps upgrades simple:

- updating ``fast-forward/dev-tools`` changes the packaged skill source;
- rerunning ``skills`` repairs missing or broken links;
- consumer-specific directories are preserved when they are not symlinks.

How to Run It
-------------

.. code-block:: bash

   composer dev-tools skills
   vendor/bin/dev-tools skills

What the Command Does
---------------------

.. list-table::
   :header-rows: 1

   * - Situation
     - Behavior
   * - ``.agents/skills`` is missing
     - Creates the directory in the consumer repository.
   * - A packaged skill is missing locally
     - Creates a symlink that points to the packaged skill directory.
   * - A valid symlink already exists
     - Leaves the link unchanged.
   * - A symlink is broken
     - Removes it and recreates it with the current packaged target.
   * - A real directory already exists at the target path
     - Preserves the directory and skips link creation to avoid overwriting
       consumer-owned content.

When to Run It Manually
-----------------------

Run ``skills`` explicitly when:

- you upgraded ``fast-forward/dev-tools`` and want to refresh local skill
  links;
- someone deleted or broke entries inside ``.agents/skills``;
- Composer plugins were disabled during install, so ``dev-tools:sync`` did not
  run automatically;
- you are iterating on packaged skills and want to verify the consumer-facing
  links without rerunning the entire repository sync flow.

Relationship with ``dev-tools:sync``
------------------------------------

``dev-tools:sync`` ends by running ``gitignore`` and ``skills``. That means
the full onboarding command refreshes workflow stubs, repository defaults, and
packaged skills in one pass.

What the Command Does Not Overwrite
-----------------------------------

The command does not replace an existing non-symlink directory inside
``.agents/skills``. This protects local experiments, package-specific custom
skills, or directories managed by another tool.
