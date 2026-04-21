update-composer-json
===================

Updates composer.json with Fast Forward dev-tools scripts and metadata.

Description
-----------

The ``update-composer-json`` command adds or updates the composer.json file with
dev-tools integration scripts and GrumPHP configuration:

1. Adds the ``dev-tools`` script entrypoint
2. Adds the ``dev-tools:fix`` script for automated fixing
3. Adds GrumPHP extra configuration pointing to the packaged ``grumphp.yml``

Usage
-----

.. code-block:: bash

   composer update-composer-json
   composer dev-tools update-composer-json -- [options]
   vendor/bin/dev-tools update-composer-json [options]

Options
-------

``--file, -f`` (optional)
   Path to the composer.json file to update. Default: ``composer.json``.

``--dry-run``
   Preview managed ``composer.json`` drift without writing the file.

``--check``
   Exit with code ``1`` when ``composer.json`` needs an update.

``--interactive``
   Prompt before updating ``composer.json``.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Update the default composer.json:

.. code-block:: bash

   composer dev-tools update-composer-json

Update a specific file:

.. code-block:: bash

   composer dev-tools update-composer-json --file=composer.json

Behavior
---------

- If the target composer.json does not exist, the command exits silently with code 0.
- Existing scripts with the same name are overwritten.
- The GrumPHP extra configuration is merged with existing configuration.
- ``--dry-run`` and ``--check`` render a diff against the managed
  ``composer.json`` result before deciding whether to write.

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. File updated or didn't exist.
   * - 1
     - Failure. Write error.
