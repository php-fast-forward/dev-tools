dependencies
=============

Analyzes missing, unused, and outdated Composer dependencies.

Description
-----------

The ``dependencies`` command (alias: ``deps``) analyzes missing, unused, and
overly outdated Composer dependencies using three tools:

- ``composer-unused`` - detects unused packages
- ``composer-dependency-analyser`` - detects missing packages
- ``jack breakpoint`` - fails when too many outdated packages accumulate

These analyzers ship as direct dependencies of ``fast-forward/dev-tools``, so
consumer repositories do not need extra setup before running the command.

Usage
-----

.. code-block:: bash

   composer dependencies
   composer dependencies [options]

   composer deps
   vendor/bin/dev-tools dependencies [options]

Options
-------

``--max-outdated=<count>`` (optional)
   Maximum number of outdated packages allowed by ``jack breakpoint``.

   Default: ``5``.

``--upgrade`` (optional)
   Applies the Jack upgrade workflow before the analyzers:

   - ``vendor/bin/jack raise-to-installed``
   - ``vendor/bin/jack open-versions``
   - ``composer update -W``
   - ``composer normalize``

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

   composer dependencies --max-outdated=10

Preview the upgrade workflow:

.. code-block:: bash

   composer dependencies --dev

Apply the upgrade workflow and then analyze dependencies:

.. code-block:: bash

   composer dependencies --upgrade --dev

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
--------

- Always previews or applies ``jack raise-to-installed`` first and then
  ``jack open-versions`` before running the analyzers.
- Runs ``composer-unused``, ``composer-dependency-analyser``, and
  ``jack breakpoint`` after the Jack preview or upgrade phase.
- ``composer-dependency-analyser`` is configured with:
  - ``--ignore-unused-deps`` (leaves unused detection to ``composer-unused``)
  - ``--ignore-prod-only-in-dev-deps`` (ignores dev-only usage in production code)
- ``jack breakpoint`` maps ``--max-outdated`` to Jack's ``--limit`` option.
- ``--upgrade`` applies Jack's ``raise-to-installed`` and ``open-versions``
  commands before ``composer update -W`` and ``composer normalize``.
- Returns a non-zero exit code when missing, unused, or too many outdated
  dependencies are found.
- All three tools must be available in ``vendor/bin/``.
