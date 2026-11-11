copy-resource
============

Copies packaged or local resources into the consumer repository.

Description
-----------

The ``copy-resource`` command copies files or directories from a source path to a
target path. The source is resolved using ``FileLocatorInterface``, while the
target is resolved to an absolute path using ``FilesystemInterface``.

This command is especially useful for:

- Copying docblock templates, license templates, or other packaged resources
- Copying resources during project initialization
- Bulk copying from directory sources

Usage
-----

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" copy-resource --source <path> --target <path>
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" copy-resource --source <path> --target <path> [--overwrite] [--dry-run] [--check] [--interactive]
   vendor/bin/dev-tools copy-resource --source <path> --target <path> [--overwrite] [--dry-run] [--check] [--interactive]

Options
-------

``--source, -s``
   Source file or directory to copy. This option is **required**.

``--target, -t``
   Target file or directory path. This option is **required**.

``--overwrite, -o``
   Overwrite existing target files. Without this option, existing files
   are skipped. When a text file changes, the command shows a unified diff
   before copying. Unchanged targets are reported as skipped, and binary or
   unreadable files fall back to a clear non-diff message.

``--dry-run``
   Preview drift for existing managed resources without writing files.

``--check``
   Exit with code ``1`` when an existing managed resource differs from the
   packaged source.

``--interactive``
   Prompt before replacing a drifted resource.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Copy a docblock template:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" copy-resource --source resources/docblock --target .docheader

Copy a directory of resources:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" copy-resource --source resources/git-hooks --target .git/hooks --overwrite

Copy and overwrite an existing file:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" copy-resource --source .editorconfig --target .editorconfig --overwrite

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. All resources were copied or skipped.
   * - 1
     - Failure. Missing required options or copy error.

Preview safety
--------------

Missing targets are reported as planned creations. ``--dry-run`` returns success
and ``--check`` returns failure without creating files or parent directories,
including when overwrite or interactive options are also supplied. When both
preview flags are supplied, ``--check`` determines the failure status. Existing
targets are compared without replacement or permission changes.
