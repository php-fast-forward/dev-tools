---
name: sphinx-docs
description: Generate or refresh Sphinx reStructuredText documentation for Fast Forward PHP packages. Use when an agent needs to create package docs in `docs/`, expand existing `.rst` sections, document APIs or workflows, or keep Fast Forward documentation structure consistent.
---

# Fast Forward Sphinx Docs

This skill provides a comprehensive checklist and structure for generating rich, congruent, and reusable documentation in reStructuredText (.rst) for all libraries in the Fast Forward PHP framework ecosystem. Use these guidelines to ensure consistency, depth, and usability across all package docs.

## General Principles
- All documentation must be written in clear, technical English.
- **Write for inexperienced users**: Include context, explain why steps matter, and anticipate questions. New developers should be able to follow the documentation without additional research.
- Use Sphinx/reStructuredText (.rst) format with proper indentation and directives.
- Structure documentation for both beginners and advanced users.
- Ensure congruence in structure, terminology, and navigation across all libraries.
- Provide practical, real-world examples for all major features and edge cases.
- Reference relevant PSRs, RFCs, and related libraries where appropriate.
- Always detect and document special features in the codebase: aliases, factories, singleton patterns, extensibility points, static helpers, PSR compliance, and integration hooks.

## Recommended Directory and File Structure

- docs/
  - index.rst
  - getting-started/
    - index.rst
    - installation.rst
    - quickstart.rst
  - usage/
    - index.rst
    - getting-services.rst
    - use-cases.rst
    - [one .rst per specialized response/stream, e.g., html-response.rst, json-response.rst, text-response.rst, empty-response.rst, redirect.rst, stream-usage.rst]
  - api/
    - index.rst
    - [one .rst per major class or interface, or grouped by topic]
    - [aliases.rst if applicable]
  - advanced/
    - index.rst
    - integration.rst
    - [aliases.rst if applicable]
    - [other advanced topics, e.g., extension, middleware, error handling, performance, custom factories, override patterns]
  - links/
    - index.rst
    - dependencies.rst
    - [coverage.rst, testdox.rst if available]
  - faq.rst
  - [compatibility.rst or version-table.rst if relevant]

## Minimum Required Topics and Content

### index.rst
- Project summary and positioning in the ecosystem.
- Useful links (GitHub, Coverage, API Reference, Issue Tracker, Packagist).
- .. toctree:: with all major sections and maxdepth 2.

### getting-started/
- **installation.rst**: Requirements, install command, supported PHP versions, dependency notes, optional dependencies, integration tips.
- **quickstart.rst**: Minimal working example, with code blocks and output comments.
- **index.rst**: Overview, key features, and toctree for the section.

### usage/
- **getting-services.rst**: How to instantiate or retrieve main classes (direct, via container, via static helpers, etc.).
- **[one .rst per specialized response/stream]**: Document each response/stream type (HTML, JSON, Text, Empty, Redirect, Stream, etc) with examples, use cases, best practices, and gotchas.
- **use-cases.rst**: Common and advanced usage scenarios, with code samples for each, including REST, streaming, redirects, error handling, etc.
- **index.rst**: Section summary and toctree.

### api/
- **index.rst**: List and describe all main classes, interfaces, traits, and aliases.
- One .rst per major class/interface, or group by topic (e.g., responses, payloads, headers, factories, aliases).
- For each: describe purpose, public API, usage, extension points, gotchas, and static helpers.
- Use tables or lists to summarize classes/interfaces, their responsibilities, and relationships.
- Document singleton patterns, aliasing, and extensibility if present.

### advanced/
- **integration.rst**: How to integrate with frameworks, containers (PSR-11), or other libraries. Include configuration examples, best practices, and troubleshooting.
- **aliases.rst**: If the library provides aliases or singleton patterns, document their usage, override, and extensibility.
- Other advanced topics: extension, middleware, error handling, performance, custom factories, override patterns, static helpers, and advanced troubleshooting.
- **index.rst**: Section summary and toctree.

### links/
- **dependencies.rst**: List all direct and indirect dependencies, with links and short descriptions. Include optional dependencies and integration notes.
- **coverage.rst**: Link to live coverage and testdox reports if available.
- **index.rst**: Section summary and toctree.
- Add links to RFCs, PSRs, and related FastForward packages.

### faq.rst
- At least 8-12 questions, including installation, usage, integration, troubleshooting, advanced topics, aliases, override/customization, and static helpers.
- Include troubleshooting tips, best practices, and links to relevant sections.
- Add questions about version compatibility, container integration, and how to access special features (e.g., ServerRequest, singleton, etc).


## Special Cases and Best Practices
- For HTTP/message libraries, always document PSR-7/PSR-15 compliance, extension points, and specialized responses/streams.
- For container/config libs, document service/provider registration, autowiring, and aliasing.
- For middleware, show pipeline usage, error handling, and integration with PSR-15.
- For all: add version compatibility tables, roadmap/upgrade notes, and highlight optional dependencies.
- Document singleton patterns, static helpers, and how to override/extend factories or aliases.
- Use .. code-block:: php for all code samples, with output comments and best practices.
- Use .. toctree:: in every index.rst for navigation.
- Cross-link to related packages, RFCs, PSRs, and FastForward ecosystem docs.
- Use tables for summarizing classes, interfaces, features, and alias mappings.
- Add a section for extending/customizing the library and for advanced troubleshooting.
- Always detect and document any special scenario found in the codebase: static methods, singleton, extensibility, aliasing, integration hooks, etc.


## Example Toctree for index.rst

.. toctree::
  :maxdepth: 2
  :caption: Contents:

  getting-started/index
  usage/index
  advanced/index
  api/index
  links/index
  faq
  [compatibility or version-table if present]

---

---

## How to Use This Skill

1. Use as a checklist and template for all new or updated documentation in the Fast Forward PHP framework ecosystem.
2. When generating docs, always analyze the codebase for:
   - Specialized responses/streams, static helpers, singleton/alias patterns, extensibility points, PSR compliance, and integration hooks.
   - Integration with containers (PSR-11), service providers, and override/customization patterns.
   - Optional dependencies, version compatibility, and advanced troubleshooting scenarios.
3. Always create or update sections to document any special scenario or advanced feature found in the codebase, mesmo que não esteja presente em outras libs.
4. Follow the suggested structure, but add extra files/sections as needed to cover all the library's features and unique selling points.

## Reference Guide

| Need | Reference |
|------|-----------|
| Directory and file structure | [references/structure.md](references/structure.md) |
| reStructuredText formatting examples | [references/rst-examples.md](references/rst-examples.md) |
| When and how to update docs during code changes | [references/update-triggers.md](references/update-triggers.md) |
