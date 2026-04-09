# reStructuredText Code Examples

Real examples of proper RST formatting for Fast Forward documentation.

**Important**: Write descriptive texts that help inexperienced users. Include context, explain why steps matter, and anticipate questions.

## Code Blocks

```rst
Basic PHP example:

.. code-block:: php

   use FastForward\Component\Class;
   $instance = new Class();
   $result = $instance->doSomething();

With output comment:

.. code-block:: php

   use FastForward\Http\Message\Request;
   $request = new Request('GET', 'https://example.com');
   // $request is a Psr\Http\Message\RequestInterface
```

## Class Documentation

```rst
Request
=======

A PSR-7 compatible HTTP request implementation.

Purpose
-------

Represents an outgoing client request, including URI, method,
headers, and body.

Public API
----------

- ``__construct(string $method, UriInterface $uri)``
- ``withMethod(string $method): self``
- ``getUri(): UriInterface``
- ``withUri(UriInterface $uri, bool $preserveBody = false): self``

Usage
-----

.. code-block:: php

   use FastForward\Http\Message\Request;
   use FastForward\Http\Message\Uri;

   $uri = new Uri('https://example.com/api');
   $request = new Request('POST', $uri);
   $request = $request->withHeader('Content-Type', 'application/json');

Extension Points
----------------

Override ``createFromGlobals()`` in subclasses for custom request
creation. Use ``withUri()`` with ``preserveBody=true`` to preserve
request body during URI changes.

See Also
--------

- :doc:`response`
- :doc:`uri`
- `PSR-7 Specification <https://www.php-fig.org/psr/psr-7/>`_
```

## Table Format

```rst
API Summary
-----------

+---------------------------+----------------------------------------+
| Method                    | Description                            |
+===========================+========================================+
| ``get(string $key)``     | Retrieves a value by key               |
+---------------------------+----------------------------------------+
| ``set(string $key, $val)``| Stores a key-value pair                |
+---------------------------+----------------------------------------+
| ``has(string $key): bool``| Checks if key exists                  |
+---------------------------+----------------------------------------+
| ``delete(string $key)``  | Removes a key-value pair               |
+---------------------------+----------------------------------------+
```

## Toctree in Index

```rst
=======
Getting Started
=======

Learn how to install and use this package.

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   installation
   quickstart

Key concepts covered:

- **Installation**: Composer setup and requirements
- **Quickstart**: Your first working example in 5 minutes
```

## Installation Section

```rst
============
Installation
============

Requirements
------------

- PHP 8.3 or higher
- Composer

Install via Composer:

.. code-block:: bash

   composer require fast-forward/component

Optional Dependencies
---------------------

For full functionality, also install:

.. code-block:: bash

   composer require fast-forward/optional-extension

See :doc:`../links/dependencies` for details.
```

## Links Section

```rst
=======
Links
=======

- `GitHub Repository <https://github.com/php-fast-forward/component>`_
- `Packagist <https://packagist.org/packages/fast-forward/component>`_
- `Issue Tracker <https://github.com/php-fast-forward/component/issues>`_
- :doc:`../links/dependencies`
- `PSR-7 <https://www.php-fig.org/psr/psr-7/>`_
- `PSR-11 <https://www.php-fig.org/psr/psr-11/>`_
```

## Key Patterns

1. Use `.. code-block:: php` for all PHP code
2. Use double-backticks for inline code: ``$var``
3. Use tables for API summaries with `+---+` syntax
4. Always include `:doc:` links to other documentation sections
5. Use headings with `=======` underline (equals) for page titles
6. Use `-------` underline (dash) for section headers