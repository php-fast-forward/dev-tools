wiki
====

Generates API documentation in Markdown format.

Description
-----------

The ``wiki`` command generates Markdown documentation using phpDocumentor.
It is especially useful together with the reusable wiki workflow for GitHub wiki.

Usage
-----

.. code-block:: bash

   composer wiki
   composer dev-tools wiki -- [options]
   vendor/bin/dev-tools wiki [options]

Options
-------

``--target, -t`` (optional)
   Path to the output directory for the generated Markdown documentation.
   Default: ``.github/wiki``.

``--cache-dir`` (optional)
   Path to the cache directory for phpDocumentor.
   Default: ``tmp/cache/phpdoc``.

``--init``
   Initialize the configured wiki target as a Git submodule.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate wiki docs:

.. code-block:: bash

   composer wiki

Generate to custom directory:

.. code-block:: bash

   composer wiki --target=wiki

Initialize wiki as submodule:

.. code-block:: bash

   composer wiki --init

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Documentation generated.
   * - 1
     - Failure. Generation error.

Behavior
---------

- Default output directory is ``.github/wiki``.
- Uses the Markdown template from ``vendor/saggre/phpdocumentor-markdown/themes/markdown``.
- The ``--init`` option creates the wiki as a Git submodule pointing to the repository wiki.
