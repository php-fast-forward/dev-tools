Rector Rules
============

FastForward DevTools ships custom Rector rules in ``src/Rector/`` to support
the PHPDoc and cleanup workflow.

.. list-table::
   :header-rows: 1

   * - Class
     - Enabled by default
     - Summary
   * - ``FastForward\DevTools\Rector\AddMissingMethodPhpDocRector``
     - Yes
     - Infers ``@param``, ``@return``, and ``@throws`` tags.
   * - ``FastForward\DevTools\Rector\RemoveEmptyDocBlockRector``
     - Yes
     - Removes empty class and method docblocks.
   * - ``FastForward\DevTools\Rector\AddMissingClassPhpDocRector``
     - No
     - Generates a basic class docblock with an optional ``@package`` tag.

The packaged ``rector.php`` enables only
``FastForward\DevTools\Rector\AddMissingMethodPhpDocRector`` and
``FastForward\DevTools\Rector\RemoveEmptyDocBlockRector``.
``FastForward\DevTools\Rector\AddMissingClassPhpDocRector`` is available for
explicit opt-in in projects that want it.
