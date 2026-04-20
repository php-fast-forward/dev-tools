Quickstart
==========

This walkthrough is the fastest way to get a new library into a healthy state.

1. Install the package.
2. Create a minimal guide directory.
3. Synchronize shared automation, packaged skills, and packaged agents.
4. Run the focused commands once.
5. Run the full suite before opening a pull request.

Create the Minimum Guide
------------------------

The ``docs`` command fails early when ``docs/`` does not exist. A tiny
starting page is enough for the first successful run.

Create the directory:

.. code-block:: bash

   mkdir -p docs

Create ``docs/index.rst`` with content such as:

.. code-block:: rst

   Documentation
   =============

   Welcome to the project documentation.

Run the First Commands
----------------------

Once the package is installed and the guide directory exists, run:

.. code-block:: bash

   composer dev-tools:sync
   composer dev-tools skills
   composer agents
   composer dev-tools tests
   composer dev-tools docs
   composer dev-tools

What Each Command Proves
------------------------

- ``composer dev-tools:sync`` proves the consumer repository can receive the
  shared scripts, automation assets, packaged skills, and packaged agents
  during onboarding.
- ``composer dev-tools skills`` proves the packaged skill set can be linked
  safely into ``.agents/skills`` without copying files into the consumer
  repository.
- ``composer agents`` proves the packaged project-agent prompts can be linked
  safely into ``.agents/agents`` without copying files into the consumer
  repository.
- ``composer dev-tools tests`` proves the packaged or local PHPUnit
  configuration can execute the current test suite.
- ``composer dev-tools docs`` proves the PSR-4 source paths and the guide
  directory are usable by phpDocumentor.
- ``composer dev-tools`` proves the complete pipeline can run in the expected
  order.

When You Want Automatic Fixes
-----------------------------

If you want the tools to modify files for you, run:

.. code-block:: bash

   composer dev-tools:fix

That is the quickest way to let Rector, PHPDoc automation, and ECS fix what
they can before you start manual cleanup.
