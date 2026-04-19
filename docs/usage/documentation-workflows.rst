Documentation Workflows
=======================

FastForward DevTools treats documentation as a combination of API extraction
and human-written guides.

How the HTML Documentation Build Works
--------------------------------------

The ``docs`` command reads two inputs:

- PSR-4 namespace paths from ``composer.json`` for the API reference;
- the ``docs/`` directory for the guide pages written in reStructuredText.

Important Command Options
-------------------------

.. list-table::
   :header-rows: 1

   * - Command
     - Option
     - Default
     - Purpose
   * - ``docs``
     - ``--source``
     - ``docs``
     - Selects the guide source directory.
   * - ``docs``
     - ``--target``
     - ``build``
     - Selects the HTML output directory.
   * - ``docs``
     - ``--template``
     - ``vendor/fast-forward/phpdoc-bootstrap-template``
     - Selects the phpDocumentor template.
   * - ``wiki``
     - ``--target``
     - ``.github/wiki``
     - Selects the Markdown API output directory.

Common Examples
---------------

.. code-block:: bash

   composer docs
   vendor/bin/dev-tools docs --source=docs --target=build
   composer wiki
   composer reports

What Each Command Is For
------------------------

- ``docs`` builds the HTML documentation site. It fails early if the source
  guide directory does not exist.
- ``wiki`` builds Markdown API pages intended for ``.github/wiki``.
- ``reports`` runs ``docs --target build`` and
  ``tests --coverage build/coverage`` and then
  ``metrics --target build/metrics --junit build/coverage/junit.xml``.

Outputs to Expect
-----------------

- an HTML site rooted at the target directory chosen for ``docs``;
- guide pages generated from ``docs/``;
- coverage data under ``build/coverage`` when ``reports`` or
  ``tests --coverage`` is used;
- metrics data under ``build/metrics`` when ``reports`` or ``metrics`` is
  used;
- Markdown API pages under ``.github/wiki`` when ``wiki`` is used.

Troubleshooting
---------------

If ``docs`` fails immediately, check the following:

1. ``docs/`` exists.
2. ``composer.json`` contains PSR-4 paths.
3. ``vendor/bin/phpdoc`` is installed.
4. The selected template path exists.
