# Documentation Update Triggers

When to update Sphinx docs during code development.

## Triggers by Change Type

| Code Change | Doc Action Required |
|-------------|-------------------|
| New public class/interface | Add api/class-name.rst |
| New public method | Update existing api/*.rst |
| New dependency added | Update links/dependencies.rst |
| New feature added | Add to usage/use-cases.rst |
| New integration point | Add to advanced/integration.rst |
| Breaking change | Add to compatibility.rst or version notes |
| New FAQ common question | Add to faq.rst |
| PHP version change | Update installation.rst requirements |

## Update Priority

1. **Critical**: API changes, new installation requirements
2. **High**: New features, new use cases, breaking changes
3. **Medium**: FAQ additions, dependency updates
4. **Low**: Links cleanup, structure refinements

## Documentation Generation Commands

```bash
# Build HTML docs locally
composer dev-tools docs

# View in browser (from public/)
# Or serve locally
cd public/ && php -S localhost:8000
```

## Anti-Patterns

- Do not document features that don't exist in code
- Do not skip API documentation when adding new classes
- Do not use markdown - RST is required
- Do not forget toctree entries for new files
- Do not leave code blocks without language: `.. code-block:: php`
