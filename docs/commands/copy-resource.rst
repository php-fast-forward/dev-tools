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

   composer copy-resource --source <path> --target <path>
   composer dev-tools copy-resource -- --source <path> --target <path> [--overwrite] [--dry-run] [--check] [--interactive]
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

Examples
--------

Copy a docblock template:

.. code-block:: bash

   composer copy-resource --source resources/docblock --target .docheader

Copy a directory of resources:

.. code-block:: bash

   composer copy-resource --source resources/git-hooks --target .git/hooks --overwrite

Copy and overwrite an existing file:

.. code-block:: bash

   composer copy-resource --source .editorconfig --target .editorconfig --overwrite

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
