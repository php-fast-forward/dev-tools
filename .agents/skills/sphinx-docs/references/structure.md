# Sphinx Documentation Structure

Detailed file structure for Fast Forward PHP package docs.

## Standard Directory Layout

```
docs/
├── index.rst              # Main entry point
├── faq.rst                # Frequently asked questions
├── compatibility.rst     # Version compatibility (if needed)
├── getting-started/
│   ├── index.rst         # Section intro + toctree
│   ├── installation.rst  # Installation requirements and commands
│   └── quickstart.rst    # Minimal working example
├── usage/
│   ├── index.rst         # Section intro + toctree
│   ├── getting-services.rst  # How to instantiate/retrieve services
│   └── use-cases.rst     # Common usage scenarios
├── api/
│   ├── index.rst         # API overview + toctree
│   ├── class-name.rst    # One file per major class/interface
│   └── aliases.rst       # Alias mappings (if applicable)
├── advanced/
│   ├── index.rst         # Section intro + toctree
│   ├── integration.rst  # Framework/container integration
│   └── customization.rst # Extending/overriding patterns
└── links/
    ├── index.rst         # Section intro + toctree
    └── dependencies.rst # Dependency list
```

## File Naming Conventions

- Use kebab-case: `getting-services.rst`, `use-cases.rst`
- One file per class: `request.rst`, `response.rst`
- Group related: `responses.rst` only if truly grouped, not listing every variant
- Index files: always `index.rst` in each subdirectory

## Required Index Structure

Each `index.rst` follows this pattern:

```rst
=======
Section Title
=======

Overview paragraph explaining what this section covers.

.. toctree::
   :maxdepth: 2
   :caption: Contents:

   installation
   quickstart

Key points covered:

- First important point
- Second important point
```

## Toctree Placement Rules

- Always put toctree at top or immediately after summary paragraph
- Use `:maxdepth: 2` for section indexes, `:maxdepth: 1` for main index
- Include `:caption:` for user-facing section labels
- List files without `.rst` extension

## Minimal vs Full Documentation

| Package Type | Required Sections |
|--------------|-------------------|
| Simple utility | index, getting-started, api, links |
| PSR implementation | + advanced/integration |
| Framework component | + usage/*, faq, advanced/* |
| Complex library | + advanced/customization, compatibility |