license
=======

Generates a LICENSE file from composer.json license information.

Description
-----------

The ``license`` command generates a LICENSE file if one does not exist and a
supported license is declared in composer.json.

Usage
-----

.. code-block:: bash

   composer license
   composer license [options]
   composer dev-tools license -- [options]
   vendor/bin/dev-tools license [options]

Options
-------

``--target`` (optional)
   Path to the target LICENSE file. Default: ``LICENSE``.

``--dry-run``
   Preview managed ``LICENSE`` drift without writing the file.

``--check``
   Exit with code ``1`` when ``LICENSE`` needs an update.

``--interactive``
   Prompt before writing the generated ``LICENSE`` content.

``--json``
   Emit a structured machine-readable payload instead of the normal terminal
   output.

``--pretty-json``
   Emit the same structured payload with indentation for terminal inspection.

Examples
--------

Generate LICENSE file:

.. code-block:: bash

   composer license

Generate to custom path:

.. code-block:: bash

   composer license --target=LICENCE

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. LICENSE generated or already exists.
   * - 1
     - Failure. Write error.

Behavior
---------

- Reads the ``license`` field from ``composer.json``.
- Supports common open-source licenses: MIT, Apache-2.0, BSD-2-Clause, BSD-3-Clause,
  GPL-3.0, LGPL-3.0, ISC, MPL-2.0.
- Resolves placeholders using information from ``composer.json``.
- Compares the generated content with the existing file before deciding whether
  to write.
