DevTools 2 Migration and Extraction Strategy
============================================

Dependency model
----------------

Version 1 made DevTools a development dependency of libraries that DevTools
might itself need at runtime. A library's ``require-dev`` is not installed
transitively when another project installs that library, so this is not always
a literal Composer solver cycle. It still creates a development feedback loop:
released DevTools constraints, local component versions, plugin activation and
shared QA tooling can constrain or bootstrap each other.

Version 2 separates three contracts:

* Composer dependencies provide application/library PHP APIs.
* CPX provides the independently resolved DevTools executable and bundled tools.
* LLM Skills provides procedural instructions from an explicitly chosen owner.

A released config/container library can therefore be required by DevTools while
its own repository invokes DevTools externally. Do not move DevTools into a
global Composer dependency of the component or install it back into the
consumer in CI. ``--skip-local`` makes the isolated execution policy explicit.

A CPX process boundary does not isolate every class loaded by a nested tool.
PHPUnit tests, custom extensions, Rector rules and configuration imports can
still share APIs. Keep directly used test libraries in the consumer as needed
and test real compatibility; do not remove every development dependency just
because DevTools no longer needs to be one.

Version 2 contract
------------------

* DevTools is a library with ``bin/dev-tools``, without a Composer plugin,
  command provider, proxy commands or automatic post-install/update sync.
* Consumers select an exact published runtime version, for example
  ``cpx --skip-local fast-forward/dev-tools:2.0.0 tests`` after that release
  has been published and independently verified.
* ``runtime --json --no-ansi --no-interaction`` supplies actual binary,
  autoload, package/project root and version paths to automation.
* CI uses the CPX runtime for consumers and the checked-out binary for DevTools
  itself. The project's Composer installation stays separate.
* ``code-style`` checks normalize/ECS without a preliminary
  ``composer update --lock``. Manifest normalization fixes remain explicit.
* ``dev-tools:sync`` remains an explicit operation for metadata, workflow
  callers, repository defaults, hooks and local Wiki generation.
* Non-global ``self-update`` runs ``cpx update fast-forward/dev-tools``; global
  installation retains ``composer global update``. Neither route changes a
  consumer's exact reviewed runtime or skill-source pin.
* Composer Normalize runs as a plugin from the isolated runtime root against
  the absolute consumer manifest with ``--no-update-lock``.
* Hooks prefer local GrumPHP, otherwise pinned CPX GrumPHP; explicit sync copies
  local ``grumphp.yml`` without persisting temporary runtime/cache paths.
* LLM Skills replaces DevTools skill installation. ``skills`` and ``agents``
  commands and generic symlink synchronization are removed. Custom project
  role prompts remain repository-owned.

See :doc:`syncing-packaged-skills` for configuration, trust and update examples.
LLM Skills does not replace synchronization of arbitrary repository files.

Publish first, migrate consumers second
---------------------------------------

The packaged workflow callers target ``@v2.0.0`` and pass the exact
``dev-tools-version: '2.0.0'`` runtime pin where supported. These templates are
prepared for the future release; do not copy or synchronize them into consumers
until the tag and package are published and independently verified. When
updating a consumer later, update both the reusable-workflow ref and runtime
version together.

1. Validate DevTools independently against a clean consumer fixture: no
   ``fast-forward/dev-tools`` dependency, independent Composer lockfile,
   compatible PHP runtime, local configuration and actual test execution.
   Check JSON runtime metadata, packaged resources and skill payloads outside
   the development checkout.
2. Review the DevTools 2 implementation and release notes. Merge and publish
   through the existing human-controlled release lifecycle. Verify the exact
   tag/commit and Packagist availability before adopting a consumer pin.
3. Inventory each consumer's manifest/lockfile, plugin allowlist, Composer
   scripts, ``vendor/fast-forward/dev-tools`` imports, PHPUnit extension paths,
   hooks, workflow callers and skill/agent links. Refresh the inventory live;
   do not assume only Fast Forward repositories consume the public package.
4. Open one migration PR per consumer. Replace DevTools commands with pinned
   CPX or reviewed script wrappers. Remove its ``require-dev`` entry and only
   its obsolete allowlist/configuration entries; regenerate the consumer lock
   through Composer, preserving unrelated dependencies and custom scripts.
5. Configure LLM Skills with a pinned GitHub source for external tools and
   Composer Trusted for libraries actually installed. Review the skill preview,
   provenance and one-owner rule. Preserve local edited instructions.
6. Preview explicit sync. Compare workflows and hooks deliberately, preserve
   Dependabot policy, and avoid broad overwrite adoption. Replace hard-coded
   vendor runtime paths with supported runtime discovery.
7. Run focused quality commands and full CI on the exact consumer PR head.
   Verify required status identities, coverage, reports and read-only Wiki
   artifacts. Merge remains a maintainer decision.

Begin with a small component such as clock, then config/container, then a
representative repository using coverage, custom configs and report publishing.
Broaden only after those pilots establish the real compatibility contract.
This implementation does not remove dependencies in other repositories or
publish any release.

Continuous extraction
---------------------

Use one domain owner for PHP behavior, CLI, skill and release documentation.
Changelog already demonstrates the independent package/tool route. For each
future extraction:

1. Define the narrow API/CLI and output contract; avoid a dependency from the
   extracted runtime back to DevTools.
2. Move implementation, tests and its existing ``fast-forward-*`` skill to the
   new owner. Keep its procedure in ``SKILL.md`` and avoid parallel copies.
3. Publish and verify the donor's archive, executable and skill payload.
4. Update DevTools to consume the released library when it needs PHP APIs, or
   an external command when process isolation is preferable. An adapter may
   preserve orchestration while responsibility moves out.
5. Update each consumer's source and version in a reviewed PR. Use Composer
   Trusted only when the library is installed; otherwise use a pinned GitHub
   source. Do not silently fall back to the latest GitHub payload if a trusted
   installed package lacks it.
6. Remove obsolete implementation and sync phases only when their new owner
   covers the actual asset. Skills alone cannot replace metadata/workflow sync.

Keep product releases and consumer adoption independently reviewable. Runtime,
workflow and skill pins should refer to compatible verified releases; a
release-channel change is an explicit policy decision. Roll back a failed
consumer migration by reverting that PR's manifest/lock, workflow, hook and
skill configuration together. Preserve the previous pin and provenance so the
rollback does not depend on a moving branch.

Proposed issue triage
---------------------

Snapshot: open DevTools issues read on 2026-10-20. These are proposals for later
maintainer review, not issue edits or closure claims. The #195, #197 and #367
bodies were read; remaining groupings below use current issue titles and need
acceptance-criteria review before changing them.

.. list-table::
   :header-rows: 1
   :widths: 18 26 56

   * - Issues
     - Proposed disposition
     - Reason and remaining work
   * - `#195 <https://github.com/php-fast-forward/dev-tools/issues/195>`_
     - Replace installation design
     - Composer installer-paths and a required agents bundle conflict with the
       external-tool model. Separate skill distribution from custom role prompts.
   * - `#197 <https://github.com/php-fast-forward/dev-tools/issues/197>`_
     - Supersede shim prerequisite
     - CPX addresses tool dependency isolation without a Box/Scoper release
       pipeline. Retain explicit investigation of shared PHPUnit/public APIs.
   * - `#367 <https://github.com/php-fast-forward/dev-tools/issues/367>`_
     - Retain, align adoption contract
     - Independent docs/Wiki PHP library, CLI, skill and automation fit this
       strategy. Preserve publication/manual-page/report ownership gates.
   * - #336
     - Retain extraction, revise bootstrap
     - Dedicated git-hooks ownership can remove another sync responsibility
       after a real replacement exists; skills do not install hooks.
   * - #238, #240
     - Retain, reconcile owners
     - Release-safe refs and shared Actions remain necessary. Coordinate docs
       automation with #367 instead of relocating the same domain twice.
   * - #350, #351, #349, #348, #347, #144
     - Reassess scope and owner
     - Dependency guidance, documentation skills, specs and workflow tools may
       belong to independent skill/tool owners; avoid automatic new runtime deps.
   * - #14, #8, #262, #265, #266, #267, #268, #269
     - Retain capability questions
     - Static analysis, parallel tests and optional quality tools still require
       evidence and focused design; CPX changes distribution, not their value.
   * - #246, #248, #142, #163, #164
     - Revalidate against version 2
     - Matrix discovery, structured output, doctor, paths and process dispatch
       need current-code acceptance review before declaring anything complete.

Instruction hierarchy map
--------------------------

The current and target scoped instruction hierarchy both contain only root
``AGENTS.md``. Keep shared runtime, validation and publication boundaries there.
Update its skills navigation to ``resources/skills/fast-forward-*`` while
keeping package procedures in their ``SKILL.md`` entrypoints. No new child
contract or Child DOX Index is justified by moving payload directories alone.
Restore the root contract together with the payload paths if reverting this
migration.
