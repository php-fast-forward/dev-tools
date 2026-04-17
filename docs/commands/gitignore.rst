gitignore
=========

Merges and synchronizes .gitignore files.

Description
-----------

The ``gitignore`` command merges the canonical .gitignore from dev-tools
with the project's existing .gitignore.

Usage
-----

.. code-block:: bash

   composer gitignore
   composer gitignore [options]
   composer dev-tools gitignore -- [options]
   vendor/bin/dev-tools gitignore [options]

Options
-------

``--source, -s`` (optional)
   Path to the source .gitignore file (canonical). Default: packaged .gitignore.

``--target, -t`` (optional)
   Path to the target .gitignore file (project). Default: project root .gitignore.

Examples
--------

Merge .gitignore files:

.. code-block:: bash

   composer gitignore

Specify custom paths:

.. code-block:: bash

   composer gitignore --source=/path/to/source/.gitignore --target=/path/to/target/.gitignore

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. .gitignore merged successfully.
   * - 1
     - Failure. Read or write error.

Behavior
---------

- Reads the canonical .gitignore from dev-tools and merges with the project's.
- By default, the source is the packaged .gitignore and the target is the project's root.
- Duplicates are removed and entries are sorted alphabetically.
- Uses the Reader, Merger, and Writer components from the GitIgnore namespace.
