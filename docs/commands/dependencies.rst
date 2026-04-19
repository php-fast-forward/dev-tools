dependencies
=============

Analyzes missing and unused Composer dependencies.

Description
-----------

The ``dependencies`` command (alias: ``deps``) analyzes missing, unused, and
overly outdated
Composer dependencies using two tools:

- ``composer-unused`` - detects unused packages
- ``composer-dependency-analyser`` - detects missing packages
- ``jack breakpoint`` - fails when too many outdated packages accumulate

This command ships as a direct dependency of ``fast-forward/dev-tools``.

Usage
-----

.. code-block:: bash

   composer dependencies
   composer dev-tools dependencies

   composer deps
   composer dev-tools deps

   vendor/bin/dev-tools dependencies
   vendor/bin/dev-tools deps

Options
-------

``--max-outdated=<count>`` (optional)
   Maximum number of outdated packages allowed by ``jack breakpoint``.

   Default: ``5``.

``--upgrade`` (optional)
   Applies the Jack upgrade workflow before the analyzers:

   - ``vendor/bin/jack open-versions``
   - ``vendor/bin/jack raise-to-installed``
   - ``composer update -W``

   Without ``--upgrade``, the command runs the Jack workflow in preview mode
   before the analyzers.

``--dev`` (optional)
   Prioritizes dev dependencies where Jack supports it.

Examples
--------

Run dependency analysis:

.. code-block:: bash

   composer dependencies

Allow up to 10 outdated packages:

.. code-block:: bash

   composer dev-tools dependencies -- --max-outdated=10

Preview the upgrade workflow:

.. code-block:: bash

   composer dev-tools dependencies -- --dev

Apply the upgrade workflow and then analyze dependencies:

.. code-block:: bash

   composer dev-tools dependencies -- --upgrade --dev

Using the alias:

.. code-block:: bash

   composer deps

Exit Codes
---------

.. list-table::
   :header-rows: 1

   * - Code
     - Meaning
   * - 0
     - Success. No missing, unused, or excessive outdated dependencies.
   * - 1
     - Failure. A dependency analyzer or Jack reported findings or errors.

Behavior
---------

- Runs ``composer-unused``, ``composer-dependency-analyser``, and
  ``jack breakpoint``.
- ``composer-dependency-analyser`` is configured with:
  - ``--ignore-unused-deps`` (leaves unused detection to ``composer-unused``)
  - ``--ignore-prod-only-in-dev-deps`` (ignores dev-only usage in production code)
- ``jack breakpoint`` maps ``--max-outdated`` to Jack's ``--limit`` option.
- It always previews Jack's ``open-versions`` and ``raise-to-installed``
  commands before the analyzers.
- ``--upgrade`` applies Jack's ``open-versions`` and ``raise-to-installed``
  commands before ``composer update -W``.
- Returns a non-zero exit code when missing, unused, or too many outdated
  dependencies are found.
- All three tools must be available in ``vendor/bin/``.
