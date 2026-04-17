gitattributes
=============

Manages .gitattributes export-ignore rules for leaner package archives.

Description
-----------

The ``gitattributes`` command adds export-ignore entries for repository-only files and
directories to keep them out of Composer package archives.

Usage
-----

.. code-block:: bash

   composer gitattributes
   composer dev-tools gitattributes
   vendor/bin/dev-tools gitattributes

Options
-------

This command does not accept additional options.

Examples
--------

Manage export-ignore rules:

.. code-block:: bash

   composer gitattributes

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Export-ignore rules synced.
   * - 1
     - Failure. Write error.

Behavior
---------

- Adds export-ignore entries for repository-only files and directories.
- Only adds entries for paths that actually exist in the repository.
- Respects ``extra.gitattributes.keep-in-export`` configuration to keep specific
  paths in exported archives.
- Respects ``extra.gitattributes.no-export-ignore`` as an alias.
- Preserves existing custom ``.gitattributes`` rules.
- Deduplicates equivalent entries and sorts them (directories before files, then alphabetically).
- Uses CandidateProvider, ExistenceChecker, ExportIgnoreFilter, Merger,
  Reader, and Writer components from the GitAttributes namespace.
