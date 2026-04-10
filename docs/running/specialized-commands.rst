Specialized Commands
====================

Use the standalone commands when you want one tool instead of the full
``standards`` pipeline.

``tests``
---------

Runs PHPUnit with the resolved ``phpunit.xml``.

.. code-block:: bash

   composer dev-tools tests
   composer dev-tools tests -- --filter=EventTracerTest

Important details:

- local ``phpunit.xml`` is preferred over the packaged default;
- ``--coverage=<path>`` creates HTML, Testdox, Clover, and raw coverage output;
- ``--no-cache`` disables ``tmp/cache/phpunit``;
- the packaged configuration registers the DevTools PHPUnit extension.

``dependencies``
----------------

Analyzes missing and unused Composer dependencies.

.. code-block:: bash

   composer dependencies
   vendor/bin/dev-tools dependencies

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

   composer dev-tools code-style
   composer dev-tools code-style -- --fix

Important details:

- it always executes ``composer update --lock --quiet`` first;
- without ``--fix``, Composer Normalize runs in ``--dry-run`` mode;
- ECS uses local ``ecs.php`` when present, otherwise the packaged fallback.

``refactor``
------------

Runs Rector against the current project.

.. code-block:: bash

   composer dev-tools refactor
   composer dev-tools refactor -- --fix

Important details:

- without ``--fix``, Rector runs in dry-run mode;
- local ``rector.php`` is preferred when present;
- the packaged default includes Fast Forward custom Rector rules plus shared
  Rector sets.

``phpdoc``
----------

Coordinates PHP-CS-Fixer and a focused Rector pass for missing method PHPDoc.

.. code-block:: bash

   composer dev-tools phpdoc
   composer dev-tools phpdoc -- --fix

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

   composer dev-tools docs
   vendor/bin/dev-tools docs --source=docs --target=public

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

   composer dev-tools wiki

Important details:

- the default output directory is ``.github/wiki``;
- it uses the Markdown template from
  ``vendor/saggre/phpdocumentor-markdown/themes/markdown``;
- it is especially useful together with the reusable wiki workflow.

``reports``
-----------

Runs the documentation and test-report pipeline used by GitHub Pages.

.. code-block:: bash

   composer dev-tools reports

Important details:

- it calls ``docs --target public``;
- it calls ``tests --coverage public/coverage``;
- it is the reporting stage used by ``standards``.

``skills``
----------

Synchronizes packaged agent skills into the consumer repository.

.. code-block:: bash

   composer dev-tools skills
   vendor/bin/dev-tools skills

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
- it calls ``skills`` so ``.agents/skills`` contains links to the packaged
  skill set.

``gitignore``
-------------

Merges and synchronizes .gitignore files.

.. code-block:: bash

   composer dev-tools gitignore
   composer dev-tools gitignore -- --source=/path/to/source/.gitignore --target=/path/to/target/.gitignore

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

   composer dev-tools license

Important details:

- it reads the ``license`` field from ``composer.json``;
- it supports common open-source licenses (MIT, Apache-2.0, BSD-2-Clause,
  BSD-3-Clause, GPL-3.0, LGPL-3.0, and MPL-2.0);
- it resolves placeholders such as ``[year]``, ``[author]``, and
  ``[project]`` using information from ``composer.json``;
- it uses template files from ``resources/license-templates/``;
- it skips generation if a LICENSE file already exists.
