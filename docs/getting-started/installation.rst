Installation
============

Because FastForward DevTools operates as a Composer plugin, the installation process seamlessly integrates its commands directly into your project environment automatically.

Requirements
------------

Ensure you meet the basic requirements described in `Supported PHP Versions <supported-php-versions>`_.

Using Composer
--------------

Require the package as a development dependency using Composer:

.. code-block:: bash

   composer require --dev fast-forward/dev-tools:dev-main

What happens during installation?
---------------------------------

1. Composer installs the package alongside its underlying tool dependencies (``symplify/easy-coding-standard``, ``rector/rector``, ``phpunit/phpunit``, ``phpdocumentor/shim``, etc.).
2. The internal ``Plugin`` provider securely audits your ``composer.json`` file.
3. The plugin natively injects the ``composer-command-provider`` instructions containing ``DevToolsCommandProvider::class`` into your ``extra`` configuration block.
4. Finally, it initializes the ``dev-tools`` executable script shortcut globally within your project, making commands accessible via ``composer dev-tools``.
