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

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki [options]
   vendor/bin/dev-tools wiki [options]

Options
-------

``--target, -t`` (optional)
   Path to the output directory for the generated Markdown documentation.
   Default: ``.github/wiki``.

``--cache-dir`` (optional)
   Path to the cache directory for phpDocumentor.
   Default: selected workspace cache directory, usually
   ``.dev-tools/cache/phpdoc``.

``--cache``
   Force phpDocumentor caching on for this run.

``--no-cache``
   Force phpDocumentor caching off for this run.

``--init``
   Legacy opt-in: initialize the configured wiki target as a Git submodule.
   Normal ``dev-tools:sync`` and the direct wiki workflow do not use this option.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate wiki docs:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki

Generate to custom directory:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki --target=.dev-tools/wiki-generated

Initialize a legacy wiki submodule explicitly:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki --init

Generate without cache:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" wiki --no-cache

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
- When the default target does not exist yet and ``--init`` is not requested,
  the command skips with a controlled warning instead of failing.
- Repositories without PHP API surface also skip wiki generation with a
  warning, because the current wiki renderer only emits API pages.
- Cache stays enabled by default; omit both flags to keep the command default,
  pass ``--cache`` to force it on, and pass ``--no-cache`` to force it off.
- When ``--cache-dir`` is omitted, phpDocumentor keeps its default cache
  directory. The option only affects phpDocumentor when caching is enabled.
- Uses the Markdown template from ``vendor/saggre/phpdocumentor-markdown/themes/markdown``.
- The ``--init`` option creates the wiki as a Git submodule pointing to the repository wiki.
- Normal ``dev-tools:sync`` uses ``--target=.dev-tools/wiki-generated`` to
  generate local Markdown. The direct wiki workflow publishes its generated
  artifact to the separate GitHub Wiki repository without a parent gitlink.
