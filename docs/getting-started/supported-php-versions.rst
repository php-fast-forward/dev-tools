Supported PHP Versions
======================

The package targets modern PHP and ships configuration files that assume PHP
8.3 language features and tooling support.

Compatibility Summary
---------------------

.. list-table::
   :header-rows: 1

   * - Area
     - Version
     - Notes
   * - Minimum supported version
     - ``8.3``
     - Declared in ``composer.json``.
   * - Composer platform baseline
     - ``8.3.0``
     - Used to resolve dependencies consistently during development.
   * - CI matrix
     - ``8.3``, ``8.4``, ``8.5``
     - Covered by the reusable ``tests.yml`` workflow.

Practical Guidance
------------------

- Treat PHP 8.3 as the compatibility floor for local development.
- If a consumer library tests against newer engines, keep the local minimum at
  8.3 unless the library itself is intentionally raising its baseline.
- When debugging dependency resolution, remember that the package sets
  ``config.platform.php`` to ``8.3.0``.
