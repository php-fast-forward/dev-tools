# README Update Triggers

When to automatically update README during code changes.

## Triggers for README Review

| Change Type | Action Required |
|-------------|-----------------|
| New public method/class added | Add to API Summary table |
| New dependency added | Add to Installation or Integration |
| PHP version requirement changed | Update badges + Installation |
| New PSR implemented | Add standards badges |
| New feature added | Add to Features list |
| Breaking change released | Add to Versioning section |
| New FAQ common question | Add to FAQ section |

## Update Priority

1. **Critical** (always update): PHP version, package name, badges
2. **High** (update before PR): API changes, new dependencies, features
3. **Medium** (update if relevant): FAQ additions, directory structure, comparison
4. **Low** (periodic review): Links, licensing, contributing guidelines

## Badge Maintenance

- Test badge link: `https://github.com/php-fast-forward/<repo>/actions/workflows/tests.yml`
- Coverage link: `https://php-fast-forward.github.io/<repo>/coverage/index.html`
- Docs link: `https://php-fast-forward.github.io/<repo>/index.html`

Verify all three exist before including badges. Missing services should be omitted rather than linked to 404s.

## Common Update Patterns

### Adding New API
```markdown
## 🧰 API Summary
| Method | Description |
|--------|-------------|
| existingMethod() | Was here |
| newMethod() | NEW: Does something |
```

### Updating Features
```markdown
## ✨ Features
- Existing feature
- 🚀 NEW: Added feature
```

### Versioning Entry
```markdown
## 🛠️ Versioning & Breaking Changes
- v2.1.0: Added newMethod() for improved handling
- v2.0.0: BREAKING: Changed signature of existingMethod()
```

## Anti-Patterns

- Do not add badges for services not actively used
- Do not invent features that don't exist
- Do not link to coverage/docs when not published
- Do not use deprecated badge styles