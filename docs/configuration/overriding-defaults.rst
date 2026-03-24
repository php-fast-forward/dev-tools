Overriding Defaults
===================

When invoked, the internal toolkit instances dramatically minimize setup times by dynamically reverting to sensible, strict configuration defaults securely bundled within the ``fast-forward/dev-tools`` package itself.

However, recognizing that specific projects may have edge-cases or expanded scopes, DevTools allows you to effortlessly override these settings locally.

How to Override
---------------

To override a default setup, simply create the corresponding configuration file mapped firmly onto your generic root application path:

- ``ecs.php`` (for Code Style specifications)
- ``rector.php`` (for Application Refactoring targets)
- ``phpunit.xml`` (for managing Testing suites)

Resolution Logic
----------------

The internal execution engine (housed in ``AbstractCommand``) invokes ``getConfigFile()``. This method specifically verifies the presence of the configuration file inside your project’s working directory:

1. If the mapped configuration file *exists*, tools utilize the custom instructions provided within your project.
2. If the configuration file is *absent*, the ``dev-tools`` process securely abstracts the instruction and dynamically relays it to the standard template preserved within the Composer installation path.

This guarantees robust predictability while maintaining advanced customization capabilities reliably.
