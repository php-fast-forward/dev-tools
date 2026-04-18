Specialized Commands
====================

Use the standalone commands when you want one tool instead of the full
``standards`` pipeline.

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

Analyzes missing and unused Composer dependencies.

.. code-block:: bash

   composer dependencies

Important details:

- it ships ``shipmonk/composer-dependency-analyser`` and
  ``icanhazstring/composer-unused`` as direct dependencies of
  ``fast-forward/dev-tools``;
- it uses ``composer-dependency-analyser`` only for missing dependency checks
  and leaves unused-package reporting to ``composer-unused``;
- it returns a non-zero exit code when missing or unused dependencies are
  found.

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

   composer docs --source=docs --target=public

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

- it calls ``docs --target public``;
- it calls ``tests --coverage public/coverage --no-progress --coverage-summary``;
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

``dev-tools:sync``
------------------

Synchronizes consumer-facing automation and defaults.

.. code-block:: bash

   composer dev-tools:sync

Important details:

- it updates ``composer.json`` scripts and
  ``extra.grumphp.config-default-path``;
- it copies missing workflow stubs, ``.editorconfig``, and ``dependabot.yml``;
- it creates ``.github/wiki`` as a git submodule when the directory is
  missing.
- it calls ``gitignore`` to merge the canonical .gitignore with the project's
  .gitignore;
- it calls ``gitattributes`` to manage export-ignore rules in .gitattributes;
- it calls ``skills`` so ``.agents/skills`` contains links to the packaged
  skill set.

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

Installs Fast Forward Git hooks and initializes GrumPHP.

.. code-block:: bash

   composer git-hooks --skip-grumphp-init

Important details:

- runs ``grumphp git:init`` to register hooks with GrumPHP by default;
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
