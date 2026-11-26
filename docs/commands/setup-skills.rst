setup:skills
============

Opts a project into LLM Skills through its official Composer commands. The
alias is ``skills:setup``. This setup is independent of ordinary quality runs
and ``dev-tools:sync``; LLM Skills remains an optional consumer integration.

Usage
-----

.. code-block:: bash

   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" setup:skills --dry-run
   cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" setup:skills

Select an exact published DevTools version exposing this command. When
working on DevTools itself, use ``php bin/dev-tools setup:skills``. The selected
working directory must contain the project's ``composer.json``; use the global
``--working-dir`` option to target another project.

Options
-------

``--dry-run``
   Prints the planned commands without executing them. It does not install
   packages, generate configuration, fetch skill donors or copy skills.

``--no-sync``
   Executes dependency/configuration setup and source registration but omits
   the final ``composer skills:update``. Official source registration may still
   download and validate donor payloads; this is not an offline mode or dry run.

``--dev-tools-ref=<ref>``
   Explicitly chooses the external DevTools GitHub skill ref in a consumer.
   Without it, setup uses a 40-character hexadecimal source commit recorded for
   the executing DevTools Composer installation. If that metadata is missing
   or unsuitable, setup fails before writes and requests an explicit non-empty
   ref. Choose a reviewed release commit or supported ref; setup does not fall
   back to a moving branch. The DevTools repository uses its local payload and
   does not need this external source.

What setup changes
------------------

1. Reads the consumer manifest and checks its existing Composer plugin policy.
   An explicit denial of ``llm/skills``, including a matching wildcard or global
   false policy, stops setup before mutations. Otherwise setup enables only
   the required plugin entry when it is not already allowed.
2. Adds ``llm/skills:^1.15`` to the consumer's ``require-dev`` if neither
   dependency section already declares it. Existing constraints and placement,
   including a production ``require`` entry, are preserved. If the declared
   package is not installed, setup runs Composer install. These dependency
   operations disable plugins and scripts and respect ``config.vendor-dir``.
3. If ``skills.json`` is absent, invokes official ``composer skills:init`` in
   quick mode with synchronization deferred, target ``.agents/skills``,
   Composer trust ``fast-forward/*``, auto-sync enabled and discovery disabled.
   The official CLI generates the file; DevTools does not maintain a separate
   JSON writer or configuration schema.
4. Registers applicable sources with official ``composer skills:add`` and
   synchronization deferred, as described below.
5. Unless ``--no-sync`` is selected, invokes ``composer skills:update`` to copy
   the configured skills through LLM Skills.

There is no global installation or addition of LLM Skills to DevTools' core
requirements. The consumer's manifest, lockfile and plugin policy are the
relevant dependency changes; inspect and commit the intended results together.
Installing instructions does not install the external tools they describe.

Source registration
-------------------

.. list-table::
   :header-rows: 1

   * - Source
     - When included
     - Ref or location
   * - Project-local skills
     - Any project with at least one ``SKILL.md`` at the payload root or nested
       below it; an absent, empty or unrelated directory is not registered.
     - ``./resources/skills`` with ``--from=dir``.
   * - External DevTools skills
     - Consumer projects, including consumers that also own local skills.
       The DevTools repository itself omits this remote source.
     - ``php-fast-forward/dev-tools`` at the recorded source commit or explicit
       ``--dev-tools-ref``.
   * - Changelog skills
     - Both consumers and the DevTools repository.
     - ``php-fast-forward/changelog`` at ``^0.4.0``.

The Changelog constraint admits compatible 0.4.x releases; it is not an exact
commit pin. Subsequent updates follow that selected range through LLM Skills.
A consumer's own local payload complements its external DevTools source rather
than replacing it. Source identity, payload validation and installation metadata
remain owned by the official LLM Skills implementation.

Existing configuration and repeated setup
-----------------------------------------

An existing ``skills.json`` skips initialization; setup never passes ``--force``
to reinitialize it. Official source registration updates matching sources while
preserving their selected skill allowlists and unrelated configuration.
Previously chosen trust and synchronization policies remain in place.

For a new file, LLM Skills 1.15 applies its own defaults and omissions. Its init
options do not expose ``trusted-replace`` or ``vendor-sources`` switches, so
setup does not promise those fields or an exact hand-written JSON layout.
Advanced policy can be reviewed through the official configuration contract;
existing values are not rewritten to match a DevTools template.

Repeat setup to register current payloads and synchronize again. It does not
add another dependency entry when LLM Skills is already declared. Commands stop
on failure, but completed earlier steps may have changed the project. Inspect
``composer.json``, ``composer.lock``, ``skills.json`` and LLM Skills' generated
state before retrying; setup does not claim an atomic rollback across Composer
and network operations.

See :doc:`../usage/syncing-packaged-skills` for ongoing updates and donor
ownership policy. LLM Skills does not synchronize generic workflows, hooks,
metadata or arbitrary project-agent prompts.
