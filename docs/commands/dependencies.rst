dependencies
=============

Analyzes missing, unused, misplaced, and outdated Composer dependencies.

Description
-----------

The ``dependencies`` command (alias: ``deps``) analyzes missing, unused,
misplaced, and overly outdated Composer dependencies using two tools:

- ``composer-dependency-analyser`` - detects missing, unused, and misplaced packages
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

``--dump-usage=<package>`` (optional)
   Asks ``composer-dependency-analyser`` to dump usages for the given package
   or wildcard pattern and enables ``--show-all-usages`` automatically.

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

Dump all matched usages for one package:

.. code-block:: bash

   composer dependencies --dump-usage=symfony/console

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
     - Success. No missing, unused, misplaced, or excessive outdated dependencies.
   * - 1
     - Failure. A dependency analyzer or Jack reported findings or errors.

Behavior
--------

- Always previews or applies ``jack raise-to-installed`` first and then
  ``jack open-versions`` before running the analyzers.
- Runs ``composer-dependency-analyser`` and ``jack breakpoint`` after the Jack
  preview or upgrade phase.
- ``composer-dependency-analyser`` is configured with:
  - ``--config composer-dependency-analyser.php`` (resolved through the package
    file locator so consumer repositories can override it locally)
  - ``--dump-usages <package>`` and ``--show-all-usages`` when ``--dump-usage``
    is passed to the DevTools command
- ``jack breakpoint`` maps ``--max-outdated`` to Jack's ``--limit`` option.
- ``--upgrade`` applies Jack's ``raise-to-installed`` and ``open-versions``
  commands before ``composer update -W`` and ``composer normalize``.
- Returns a non-zero exit code when missing, unused, misplaced, or too many
  outdated dependencies are found.
- Both tools must be available in ``vendor/bin/``.
