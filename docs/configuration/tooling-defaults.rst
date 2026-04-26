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
     - ``refactor`` and ``docheader``
     - Fallback Rector configuration.
   * - ``phpunit.xml``
     - ``tests``
     - Registers ``FastForward\DevTools\PhpUnit\Runner\Extension\DevToolsExtension``.
   * - ``.php-cs-fixer.dist.php``
     - ``docheader``
     - Controls header and PHPDoc fixer behavior.
   * - ``.docheader``
     - ``docheader``
     - Created into the consumer root on demand when missing.
   * - ``.editorconfig``
     - ``dev-tools:sync``
     - Copied into the consumer root when missing.
   * - ``.agents/skills/*``
     - ``skills`` and ``dev-tools:sync``
     - Packaged agent skill directories exposed to consumer repositories
       through symlinks.
   * - ``.agents/agents/*``
     - ``agents`` and ``dev-tools:sync``
     - Packaged project-agent prompt directories exposed to consumer
       repositories through symlinks.

Generated and Cache Directories
-------------------------------

- ``.dev-tools/`` contains generated documentation and report output.
- ``.dev-tools/coverage/`` contains HTML coverage, Testdox, Clover, and raw
  coverage data.
- ``.dev-tools/metrics/`` contains PhpMetrics HTML output plus the generated
  ``report.json`` and ``report-summary.json`` artifacts.
- ``.github/wiki/`` contains generated Markdown API documentation and, in
  consumer repositories, the wiki submodule.
- ``.agents/skills/`` contains symlinked packaged skills or consumer-owned
  directories kept in place by the ``skills`` command.
- ``.agents/agents/`` contains symlinked packaged project agents or
  consumer-owned directories kept in place by the ``agents`` command.
- ``.dev-tools/cache/phpdoc``, ``.dev-tools/cache/phpunit``,
  ``.dev-tools/cache/rector``, and
  ``.dev-tools/cache/php-cs-fixer/.php-cs-fixer.cache`` store repository-local
  tool caches.

Local Versus Packaged Files
---------------------------

Commands resolve configuration from the consumer root first. When a local file
is missing, the command falls back to the packaged version shipped by
``fast-forward/dev-tools``.
