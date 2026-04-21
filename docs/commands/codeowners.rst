codeowners
==========

Generates ``.github/CODEOWNERS`` from local repository metadata.

Description
-----------

The ``codeowners`` command creates or refreshes a managed ``CODEOWNERS`` file
for the current repository. It prefers explicit GitHub profile URLs from
``composer.json`` authors metadata, falls back to commented suggestions from
``composer.json`` support metadata, and can prompt for owners when interactive
input is enabled.

Usage
-----

.. code-block:: bash

   composer codeowners
   composer codeowners [options]

Options
-------

``--file``
   Target path to manage. Defaults to ``.github/CODEOWNERS``.

``--overwrite, -o``
   Replace an existing CODEOWNERS file instead of preserving it.

``--dry-run``
   Preview managed-file drift without writing changes.

``--check``
   Report managed-file drift and exit with code ``1`` when CODEOWNERS needs an
   update.

``--interactive``
   Prompt for owners when metadata inference is insufficient and confirm before
   replacing an existing file.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate CODEOWNERS from the current repository metadata:

.. code-block:: bash

   composer codeowners

Preview drift without writing:

.. code-block:: bash

   composer codeowners --dry-run

Fail in CI when CODEOWNERS needs an update:

.. code-block:: bash

   composer codeowners --check

Prompt for explicit owners when metadata is incomplete:

.. code-block:: bash

   composer codeowners --interactive

Behavior
--------

- It reads author homepages from ``composer.json`` and converts GitHub profile
  URLs into ``@owner`` handles.
- When direct owners cannot be inferred, it uses ``composer.json`` support
  metadata to add a commented suggestion instead of writing someone else's
  ownership rules into the consumer repository.
- In interactive terminals, ``--interactive`` lets maintainers provide
  space-separated owners for the catch-all ``*`` rule before writing the file.
- By default, an existing ``.github/CODEOWNERS`` file is preserved unless
  ``--overwrite``, ``--dry-run``, ``--check``, or ``--interactive`` is used.
