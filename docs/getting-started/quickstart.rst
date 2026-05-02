Quickstart
==========

This walkthrough is the fastest way to get a new library into a healthy state.

1. Install the package.
2. Create a guide directory if the repository will publish guides.
3. Synchronize shared automation, packaged skills, and packaged agents.
4. Run the focused commands once.
5. Run the full suite before opening a pull request.

Optional Guide Setup
--------------------

If the repository will publish guides, a tiny starting page is enough for the
first successful ``docs`` run.

Create the directory:

.. code-block:: bash

   mkdir -p docs

Create ``docs/index.rst`` with content such as:

.. code-block:: rst

   Documentation
   =============

   Welcome to the project documentation.

Repositories that only ship PHP code can skip this step and still generate API
documentation. Repositories that only ship guides can also use the same
``docs`` command even without PSR-4 source paths.

Run the First Commands
----------------------

Once the package is installed, run:

.. code-block:: bash

   composer dev-tools:sync
   composer skills
   composer agents
   composer tests
   composer docs
   composer dev-tools

What Each Command Proves
------------------------

- ``composer dev-tools:sync`` proves the consumer repository can receive the
  shared scripts, automation assets, packaged skills, and packaged agents
  during onboarding.
- ``composer skills`` proves the packaged skill set can be linked
  safely into ``.agents/skills`` without copying files into the consumer
  repository.
- ``composer agents`` proves the packaged project-agent prompts can be linked
  safely into ``.agents/agents`` without copying files into the consumer
  repository.
- ``composer tests`` proves the packaged or local PHPUnit
  configuration can execute the current test suite, or skip gracefully when
  the repository intentionally has no runnable PHPUnit surface yet.
- ``composer docs`` proves the PSR-4 source paths and the guide
  directory are usable by phpDocumentor, whichever of those surfaces the
  repository actually provides.
- ``composer dev-tools`` proves the complete pipeline can run in the expected
  order.

When You Want Automatic Fixes
-----------------------------

If you want the tools to modify files for you, run:

.. code-block:: bash

   composer dev-tools:fix

That is the quickest way to let Rector, PHPDoc automation, and ECS fix what
they can before you start manual cleanup.
