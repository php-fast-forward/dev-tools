The Unified Command
===================

The default command of the local application is ``standards``. In practice,
that means the following entry points all reach the same orchestration flow:

- ``composer dev-tools``
- ``vendor/bin/dev-tools``
- ``vendor/bin/dev-tools standards``

Execution Order
---------------

``standards`` runs these commands in sequence:

1. ``refactor``
2. ``docheader``
3. ``code-style``
4. ``reports``

The command attempts every stage and returns a failing exit code if any stage
failed.

Using ``--fix``
---------------

To allow the tools to modify files, use one of the following entry points:

.. code-block:: bash

   composer dev-tools:fix
   vendor/bin/dev-tools --fix

The flag mainly affects ``refactor``, ``docheader``, and ``code-style``. The
reporting steps still run, but they do not use the flag themselves.

When the Unified Command Is the Right Choice
--------------------------------------------

Use ``standards`` before pushing, before opening a pull request, or after a
large refactor when you want one command to rebuild the expected project state.
