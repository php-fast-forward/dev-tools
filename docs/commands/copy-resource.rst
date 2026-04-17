copy-resource
============

Copies packaged or local resources into the consumer repository.

.. versionadded:: 1.6
   This command was introduced in version 1.6.

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
   composer dev-tools copy-resource -- --source <path> --target <path> [--overwrite]
   vendor/bin/dev-tools copy-resource --source <path> --target <path> [--overwrite]

Options
-------

``--source, -s``
   Source file or directory to copy. This option is **required**.

``--target, -t``
   Target file or directory path. This option is **required**.

``--overwrite, -o``
   Overwrite existing target files. Without this option, existing files
   are skipped.

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
