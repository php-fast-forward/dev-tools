Specialized Commands
====================

Use the standalone commands when you want one tool instead of the full
``standards`` pipeline.

``changelog:*``
---------------

Manages Keep a Changelog 1.1.0 files and supports the packaged changelog
workflow.

.. code-block:: bash

   composer changelog:entry --type=added "Add release automation workflow (#28)"
   composer changelog:check --against=origin/main
   composer changelog:check --format=json
   composer changelog:next-version
   composer changelog:next-version --format=json
   composer changelog:promote 1.3.0 --date=2026-04-19
   composer changelog:promote 1.3.0 --format=json
   composer changelog:show 1.3.0

Important details:

- ``changelog:entry`` creates the changelog file automatically when it does not
  exist yet;
- ``changelog:check`` is the command used by pull-request validation;
- ``changelog:check --format=json`` and
  ``changelog:next-version --format=json`` and
  ``changelog:promote --format=json`` are the initial structured-output
  rollouts for automation, CI, and AI-agent consumers;
- ``changelog:next-version`` and ``changelog:promote`` support the manual
  release-preparation workflow;
- ``changelog:show`` renders the published release body used by GitHub Release
  publication.

``tests``
---------

Runs PHPUnit with the resolved ``phpunit.xml``.

.. code-block:: bash

   composer tests
   composer tests -- --filter=EventTracerTest

Important details:

- local ``phpunit.xml`` is preferred over the packaged default;
- ``--coverage=<path>`` creates HTML, Testdox, Clover, and raw coverage output;
- ``--coverage-summary`` keeps coverage text output to PHPUnit's summary;
- ``--no-progress`` disables PHPUnit progress output;
- ``--no-cache`` disables ``tmp/cache/phpunit``;
- the packaged configuration registers the DevTools PHPUnit extension.

``dependencies``
----------------

Analyzes missing, unused, misplaced, and outdated Composer dependencies.

.. code-block:: bash

   composer dependencies
   composer dependencies --max-outdated=10
   composer dependencies --max-outdated=-1
   composer dependencies --dev
   composer dependencies --dump-usage=symfony/console
   composer dependencies --upgrade --dev

Important details:

- it ships ``shipmonk/composer-dependency-analyser`` and ``rector/jack`` as
  direct dependencies of ``fast-forward/dev-tools``;
- it uses ``composer-dependency-analyser`` for missing, unused, and misplaced
  dependency checks, with a packaged config that consumer repositories can
  override locally;
- ``--dump-usage=<package>`` forwards to
  ``composer-dependency-analyser --dump-usages <package> --show-all-usages``;
- it uses ``jack breakpoint --limit=<max-outdated>`` to fail when too many
  outdated dependencies accumulate;
- ``--max-outdated=-1`` keeps the Jack outdated report in the output but
  ignores Jack's failure so only dependency-analyser findings fail the command;
- the packaged ``tests.yml`` workflow uses ``--max-outdated=-1`` by default,
  so dependency health stays required in CI while outdated-package counts are
  reported without failing the workflow on their own;
- it previews ``jack raise-to-installed`` and ``jack open-versions`` before
  the analyzers;
- ``--upgrade`` runs ``jack raise-to-installed``, ``jack open-versions``,
  ``composer update -W``, and ``composer normalize`` before the analyzers;
- it returns a non-zero exit code when missing, unused, misplaced, or too many
  outdated dependencies are found.

``metrics``
-----------

Analyzes code metrics with PhpMetrics.

.. code-block:: bash

   composer metrics
   composer metrics --target=.dev-tools/metrics
   composer --working-dir=packages/example metrics

Important details:

- it ships ``phpmetrics/phpmetrics`` as a direct dependency of
  ``fast-forward/dev-tools``;
- when invoked through Composer, you MAY use Composer's inherited
  ``--working-dir`` option to analyze another checkout without changing
  directories first;
- ``--target`` stores the HTML report plus ``report.json`` and
  ``report-summary.json`` in the same directory for CI artifacts or manual
  review;
- it suppresses deprecation notices emitted by the PhpMetrics dependency
  itself so the command output stays readable.

``code-style``
--------------

Runs Composer Normalize and ECS.

.. code-block:: bash

   composer code-style
   composer code-style --fix

Important details:

- it always executes ``composer update --lock --quiet`` first;
- without ``--fix``, Composer Normalize runs in ``--dry-run`` mode;
- ECS uses local ``ecs.php`` when present, otherwise the packaged fallback.

``refactor``
------------

Runs Rector against the current project.

.. code-block:: bash

   composer refactor --fix

Important details:

- without ``--fix``, Rector runs in dry-run mode;
- local ``rector.php`` is preferred when present;
- the packaged default includes Fast Forward custom Rector rules plus shared
  Rector sets.

``phpdoc``
----------

Coordinates PHP-CS-Fixer and a focused Rector pass for missing method PHPDoc.

.. code-block:: bash

   composer phpdoc
   composer phpdoc --fix

Important details:

- it creates ``.docheader`` from the packaged template when the file is
  missing;
- it uses ``.php-cs-fixer.dist.php`` and ``rector.php`` through the same
  local-first fallback logic;
- the Rector phase explicitly runs
  ``FastForward\DevTools\Rector\AddMissingMethodPhpDocRector``.

``docs``
--------

Builds the HTML documentation site with phpDocumentor.

.. code-block:: bash

   composer docs --source=docs --target=.dev-tools

Important details:

- ``docs/`` must exist unless you pass another ``--source`` directory;
- API pages are built from the PSR-4 paths declared in ``composer.json``;
- guide pages are built from the selected source directory;
- ``--template`` defaults to
  ``vendor/fast-forward/phpdoc-bootstrap-template``.

``wiki``
--------

Builds Markdown API pages for a GitHub wiki.

.. code-block:: bash

   composer wiki

Important details:

- the default output directory is ``.github/wiki``;
- it uses the Markdown template from
  ``vendor/saggre/phpdocumentor-markdown/themes/markdown``;
- it is especially useful together with the reusable wiki workflow.

``reports``
-----------

Runs the documentation and test-report pipeline used by GitHub Pages.

.. code-block:: bash

   composer reports

Important details:

- it calls ``docs --target .dev-tools``;
- it calls ``tests --coverage .dev-tools/coverage --no-progress --coverage-summary``;
- it calls ``metrics --target .dev-tools/metrics --junit .dev-tools/coverage/junit.xml``;
- ``docs`` remains detached, while ``tests`` and ``metrics`` run in sequence so
  PhpMetrics can reuse the JUnit report generated by PHPUnit;
- it is the reporting stage used by ``standards``.

``skills``
----------

Synchronizes packaged agent skills into the consumer repository.

.. code-block:: bash

   composer skills

Important details:

- it verifies the packaged ``.agents/skills`` directory before doing any work;
- it creates the consumer ``.agents/skills`` directory when missing;
- it creates missing symlinks and repairs broken ones;
- it preserves an existing non-symlink directory instead of overwriting it.

``agents``
----------

Synchronizes packaged project-agent prompts into the consumer repository.

.. code-block:: bash

   composer agents

Important details:

- it verifies the packaged ``.agents/agents`` directory before doing any work;
- it creates the consumer ``.agents/agents`` directory when missing;
- it creates missing symlinks and repairs broken ones;
- it preserves an existing non-symlink directory instead of overwriting it;
- it uses the same generic synchronization rules as ``skills`` through
  ``FastForward\\DevTools\\Sync\\PackagedDirectorySynchronizer``.

``funding``
-----------

Synchronizes supported funding metadata between Composer and GitHub formats.

.. code-block:: bash

   composer funding
   composer funding --dry-run
   composer funding --check

Important details:

- it keeps ``composer.json`` ``funding`` entries and ``.github/FUNDING.yml`` in
  sync for GitHub Sponsors and ``custom`` URLs;
- it preserves unsupported Composer funding providers and unsupported YAML
  keys instead of rewriting them;
- it creates ``.github/FUNDING.yml`` when Composer already declares supported
  funding metadata;
- it supports ``--dry-run``, ``--check``, and ``--interactive`` so funding
  drift can be surfaced in CI and reviewed locally.

``codeowners``
--------------

Generates managed CODEOWNERS files from repository metadata.

.. code-block:: bash

   composer codeowners
   composer codeowners --dry-run
   composer codeowners --interactive

Important details:

- it inspects ``composer.json`` author homepages to infer GitHub handles for
  ``.github/CODEOWNERS``;
- when direct ownership cannot be inferred, it renders a commented fallback
  instead of copying hard-coded owners into the consumer repository;
- ``--interactive`` lets maintainers provide explicit owners before writing the
  catch-all ``*`` rule;
- ``dev-tools:sync`` runs ``codeowners`` automatically alongside the other
  consumer bootstrap steps.

``dev-tools:sync``
------------------

Synchronizes consumer-facing automation and defaults.

.. code-block:: bash

   composer dev-tools:sync

Important details:

- it updates ``composer.json`` scripts and
  ``extra.grumphp.config-default-path``;
- it calls ``funding`` so supported funding metadata stays aligned between
  ``composer.json`` and ``.github/FUNDING.yml``;
- it copies missing workflow stubs, ``.editorconfig``, and ``dependabot.yml``;
- it calls ``codeowners`` to generate ``.github/CODEOWNERS`` from local
  metadata;
- it creates ``.github/wiki`` as a git submodule when the directory is
  missing.
- it calls ``gitignore`` to merge the canonical .gitignore with the project's
  .gitignore;
- it calls ``gitattributes`` to manage export-ignore rules in .gitattributes;
- it calls ``skills`` so ``.agents/skills`` contains links to the packaged
  skill set;
- it calls ``agents`` so ``.agents/agents`` contains links to the packaged
  project-agent set.

``gitattributes``
----------------

Manages .gitattributes export-ignore rules for leaner Composer package archives.

.. code-block:: bash

   composer gitattributes

Important details:

- it adds export-ignore entries for repository-only files and directories;
- it only adds entries for paths that actually exist in the repository;
- it respects the ``extra.gitattributes.keep-in-export`` configuration to
  keep specific paths in exported archives;
- it preserves existing custom .gitattributes rules;
- it deduplicates equivalent entries and sorts them with directories before
  files, then alphabetically;
- it uses CandidateProvider, ExistenceChecker, ExportIgnoreFilter, Merger,
  Reader, and Writer components from the GitAttributes namespace.

``gitignore``
-------------

Merges and synchronizes .gitignore files.

.. code-block:: bash

   composer gitignore --source=/path/to/source/.gitignore --target=/path/to/target/.gitignore

Important details:

- it reads the canonical .gitignore from dev-tools and merges with the
  project's existing .gitignore;
- by default, the source is the packaged .gitignore and the target is the
  project's root .gitignore;
- duplicates are removed and entries are sorted alphabetically;
- it uses the Reader, Merger, and Writer components from the GitIgnore
  namespace.

``license``
----------

Generates a LICENSE file from composer.json license information.

.. code-block:: bash

   composer license

Important details:

- it reads the ``license`` field from ``composer.json``;
- it supports common open-source licenses (MIT, Apache-2.0, BSD-2-Clause,
  BSD-3-Clause, GPL-3.0, LGPL-3.0, and MPL-2.0);
- it resolves placeholders such as ``[year]``, ``[author]``, and
  ``[project]`` using information from ``composer.json``;
- it uses template files from ``resources/license-templates/``;
- it skips generation if a LICENSE file already exists.

``copy-resource``
-----------------

Copies packaged or local resources into the consumer repository.

.. code-block:: bash

   composer copy-resource --source <path> --target <path> --overwrite

Important details:

- source is resolved using ``FileLocatorInterface``;
- target is resolved to an absolute path using ``FilesystemInterface``;
- both ``--source`` and ``--target`` are required options;
- without ``--overwrite``, existing target files are skipped;
- supports both files and directories as sources.

``git-hooks``
-------------

Installs packaged Fast Forward Git hooks.

.. code-block:: bash

   composer git-hooks

Important details:

- copies hook files from source to target directory;
- sets executable permissions on copied hooks;
- ``--source`` defaults to ``resources/git-hooks``;
- ``--target`` defaults to ``.git/hooks``;
- ``--no-overwrite`` preserves existing hook files.

``update-composer-json``
-------------------------

Updates composer.json with Fast Forward dev-tools scripts and metadata.

.. code-block:: bash

   composer update-composer-json --file=composer.json

Important details:

- adds ``dev-tools`` script entrypoint to composer.json;
- adds ``dev-tools:fix`` script for automated fixing;
- adds GrumPHP extra configuration pointing to packaged ``grumphp.yml``;
- if the target file does not exist, exits silently with code 0;
- existing scripts with the same name are overwritten.
