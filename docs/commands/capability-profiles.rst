Project Capability Profiles
===========================

Use a project profile to choose which capabilities the ``standards`` and
``reports`` aggregators execute. Selection does not change the package's
bundled Composer requirements, install tools or download a smaller runtime.
Individual commands remain explicitly invokable regardless of the selection.

Configuration
-------------

The profile is ``dev-tools.json`` in the selected working project directory:

.. code-block:: json

   {
     "version": 1,
     "capabilities": ["tests", "quality"]
   }

Only ``version`` and ``capabilities`` are accepted. Version must be the integer
``1``. Capabilities must be a list of distinct supported strings; unknown keys,
names, duplicate names, invalid JSON and other versions fail validation.
A malformed existing file is preserved when configuration fails.

When the file is absent, aggregators retain their legacy full execution plan.
An explicit empty list is different: no checks are selected, so aggregators
fail rather than report a successful empty run. Removing the profile restores
the legacy plan; selecting every capability creates an explicit plan that also
applies project applicability and availability checks.

.. list-table::
   :header-rows: 1

   * - Capability
     - Aggregate behavior
   * - ``tests``
     - ``reports`` runs tests with coverage when the project exposes testable
       PHP source; ``standards`` delegates to that reporting plan.
   * - ``quality``
     - ``standards`` runs ``refactor``, ``phpdoc`` and ``code-style``.
   * - ``docs``
     - ``reports`` generates documentation when guides or API directories exist.
   * - ``metrics``
     - ``reports`` runs the metrics stage.
   * - ``dependencies``
     - Records dependency-analysis selection and tool availability. Run
       ``dependencies`` explicitly; it is not added to either aggregate plan.

Configure with setup
--------------------

``setup`` (alias ``tooling:configure``) offers an interactive multiple-choice
selection. Its default is the existing profile, or all capabilities when no
profile exists. Explicit options work without a prompt:

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" setup
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" setup --capability=tests --capability=quality --dry-run --no-interaction
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" setup --capability=tests --capability=quality --no-interaction

Use an exact published CLI version exposing these commands; see
:doc:`../getting-started/installation`. When developing DevTools itself, replace
the CPX invocation with ``php bin/dev-tools``.

``--capability`` is repeatable and replaces the complete selection rather than
adding entries to the previous profile. Non-interactive setup requires at
least one explicit capability. ``--dry-run`` prints the proposed profile without
writing it. Repeating the same selection does not rewrite the file; capabilities
are stored in canonical order. Setup writes only the profile and installs no
dependencies or repository assets. Commit the reviewed profile with the project.

Inspect selection and availability
---------------------------------

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tooling:status
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tooling:status --json
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tooling:status --pretty-json

Status reports every capability's ``selected`` and ``available`` flags, missing
binary names and resolved binary paths. Structured output contains ``version``,
``profile`` and ``capabilities``; ``profile`` is ``null`` in legacy mode.
Availability checks existing executable files. It does not prove compatible
versions, project readiness or a successful quality run, and does not install
missing tools. A successful status command means inspection succeeded; inspect
the individual availability flags before running an aggregate.

Aggregate failures
------------------

With an explicit profile, aggregates fail when no selected checks apply or
required binaries for their execution plan are missing. For example:

* ``["quality"]`` runs the three quality phases in ``standards`` but gives
  ``reports`` no applicable stage.
* ``["docs"]`` fails ``reports`` when the project has neither guides nor API
  directories.
* ``["dependencies"]`` gives neither aggregate an applicable stage; invoke
  dependency analysis directly.

Output-directory options such as ``reports --metrics=...`` choose paths for an
enabled stage; they do not enable a capability omitted from the profile.
See :doc:`standards` and :doc:`reports` for execution order and output options.
