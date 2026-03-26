The Unified Command
===================

FastForward DevTools exposes a highly reliable base command to orchestrate tests, generate documentation, validate constraints, and audit your code styling strictly sequentially.

Executing the QA Suite
----------------------

To run all capabilities, simply execute:

.. code-block:: bash

   composer dev-tools

This master command runs internal commands in the following sequence:

1. ``refactor``
2. ``phpdoc``
3. ``code-style``
4. ``reports``

Auto-fixing Issues
------------------

If you intend to instruct the automated tools (e.g., Rector, ECS, and PHP-CS-Fixer) to resolve style violations transparently and iteratively, append the ``--fix`` (or ``-f``) flag to the instruction:

.. code-block:: bash

   composer dev-tools:fix

Using this flag guarantees that formatting inconsistencies and missing PHPDocs are automatically patched inside your files directly, drastically reducing manual review times before submitting a Pull Request.
