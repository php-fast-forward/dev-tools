docs
====

Generates API documentation using phpDocumentor.

Description
-----------

The ``docs`` command builds HTML documentation using phpDocumentor.
It generates documentation from:

- PSR-4 paths declared in composer.json (API docs)
- Selected source directory (guide docs)

Usage
-----

.. code-block:: bash

   composer docs
   composer dev-tools docs
   composer docs [options]
   composer dev-tools docs -- [options]
   vendor/bin/dev-tools docs [options]

Options
-------

``--target, -t`` (optional)
   Path to the output directory for the generated HTML documentation.
   Default: ``.dev-tools``.

``--source, -s`` (optional)
   Path to the source directory for the guide documentation.
   Default: ``docs``.

``--template`` (optional)
   Path to the template directory for the generated HTML documentation.
   Default: ``vendor/fast-forward/phpdoc-bootstrap-template``.

``--cache-dir`` (optional)
   Path to the cache directory for phpDocumentor.
   Default: ``.dev-tools/cache/phpdoc``.

``--cache``
   Force phpDocumentor caching on for this run.

``--no-cache``
   Force phpDocumentor caching off for this run.

``--progress``
   Enable progress output from phpDocumentor.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate docs with defaults:

.. code-block:: bash

   composer docs

Generate to custom directory:

.. code-block:: bash

   composer docs --target=dist/docs

Use custom source:

.. code-block:: bash

   composer docs --source=docs/user-guide

Generate without cache:

.. code-block:: bash

   composer docs --no-cache

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Documentation generated.
   * - 1
     - Failure. Generation error or source not found.

Behavior
---------

- ``docs/`` must exist unless you pass another ``--source`` directory.
- API pages are built from the PSR-4 paths declared in ``composer.json``.
- Guide pages are built from the selected source directory.
- Cache stays enabled by default; omit both flags to keep the command default,
  pass ``--cache`` to force it on, and pass ``--no-cache`` to force it off.
- When ``--cache-dir`` is omitted, phpDocumentor keeps its default cache
  directory. The option only affects phpDocumentor when caching is enabled.
- A temporary ``phpdocumentor.xml`` is created for the run and uses the
  configured cache directory only when caching stays enabled.
- Progress output is disabled by default; use ``--progress`` to re-enable it in
  text mode.
- ``--json`` and ``--pretty-json`` suppress phpDocumentor progress rendering so
  the structured payload stays readable.
- Markers: TODO, FIXME, BUG, HACK are included.
