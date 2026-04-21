funding
=======

Synchronizes funding metadata between ``composer.json`` and
``.github/FUNDING.yml``.

Description
-----------

The ``funding`` command merges supported funding metadata declared in Composer
and GitHub formats so both files stay aligned:

1. Composer ``funding`` entries with ``type=github`` are normalized to GitHub
   Sponsors handles in ``.github/FUNDING.yml``.
2. Composer ``funding`` entries with ``type=custom`` are normalized to
   ``custom`` URLs in ``.github/FUNDING.yml``.
3. Existing ``github`` and ``custom`` entries from ``.github/FUNDING.yml`` are
   mirrored back into ``composer.json``.
4. Unsupported providers are preserved in their original format.

Usage
-----

.. code-block:: bash

   composer funding
   composer funding [options]
   composer dev-tools funding -- [options]
   vendor/bin/dev-tools funding [options]

Options
-------

``--composer-file`` (optional)
   Path to the Composer manifest to synchronize. Default:
   ``Factory::getComposerFile()``.

``--funding-file`` (optional)
   Path to the GitHub funding file to synchronize. Default:
   ``.github/FUNDING.yml``.

``--dry-run``
   Preview funding metadata drift without writing files.

``--check``
   Exit with code ``1`` when synchronized funding metadata would change either
   file.

``--interactive``
   Prompt before writing drifted funding metadata files.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Synchronize the default files:

.. code-block:: bash

   composer funding

Preview funding drift:

.. code-block:: bash

   composer funding --dry-run

Validate funding metadata in CI:

.. code-block:: bash

   composer funding --check

Use custom paths:

.. code-block:: bash

   composer funding --composer-file=packages/example/composer.json --funding-file=packages/example/.github/FUNDING.yml

Exit Codes
----------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. Funding metadata was already aligned or was updated
       successfully.
   * - 1
     - Failure. ``--check`` detected drift or a write failed.

Behavior
--------

- Supports GitHub Sponsors handles and ``custom`` URLs as synchronized managed
  entries.
- Preserves unsupported Composer funding providers and unsupported GitHub
  funding YAML keys.
- Normalizes ``composer.json`` with ``composer normalize`` after applying
  funding metadata updates.
- Creates ``.github/FUNDING.yml`` when Composer declares supported funding
  metadata and the file is missing.
- Skips writing ``.github/FUNDING.yml`` when neither side declares supported
  funding metadata.
- Renders a unified diff during ``--dry-run`` and ``--check`` so local runs and
  CI logs show the managed changes clearly.
