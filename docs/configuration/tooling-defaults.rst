Packaged Defaults
=================

The package ships the baseline files below so consumer projects do not need to
create them on day one.

.. list-table::
   :header-rows: 1

   * - File
     - Used by
     - Notes
   * - ``ecs.php``
     - ``code-style``
     - Fallback ECS configuration.
   * - ``rector.php``
     - ``refactor`` and ``phpdoc``
     - Fallback Rector configuration.
   * - ``phpunit.xml``
     - ``tests``
     - Registers ``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``.
   * - ``.php-cs-fixer.dist.php``
     - ``phpdoc``
     - Controls header and PHPDoc fixer behavior.
   * - ``.docheader``
     - ``phpdoc``
     - Created into the consumer root on demand when missing.
   * - ``.editorconfig``
     - ``dev-tools:sync``
     - Copied into the consumer root when missing.
   * - ``.agents/skills/*``
     - ``skills`` and ``dev-tools:sync``
     - Packaged agent skill directories exposed to consumer repositories
       through symlinks.

Generated and Cache Directories
-------------------------------

- ``build/`` contains generated documentation and report output.
- ``build/coverage/`` contains HTML coverage, Testdox, Clover, and raw
  coverage data.
- ``.github/wiki/`` contains generated Markdown API documentation and, in
  consumer repositories, the wiki submodule.
- ``.agents/skills/`` contains symlinked packaged skills or consumer-owned
  directories kept in place by the ``skills`` command.
- ``tmp/cache/phpdoc``, ``tmp/cache/phpunit``, ``tmp/cache/rector``, and
  ``tmp/cache/.php-cs-fixer.cache`` store tool caches.

Local Versus Packaged Files
---------------------------

Commands resolve configuration from the consumer root first. When a local file
is missing, the command falls back to the packaged version shipped by
``fast-forward/dev-tools``.
