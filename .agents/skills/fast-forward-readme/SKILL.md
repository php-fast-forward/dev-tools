---
name: fast-forward-readme
description: Generate or refresh README.md files for Fast Forward PHP packages. Use when Codex needs to create a new package README, reorganize an existing README, improve onboarding and discoverability, or align README structure with the Fast Forward ecosystem.
---

# Fast Forward README

## Purpose
This skill provides a comprehensive, reusable checklist and structure for creating high-quality, consistent, and discoverable `README.md` files for any component in the Fast Forward PHP framework ecosystem. It ensures:
- All relevant sections are present and in a logical order
- Consistency of terminology, formatting, and navigation
- Coverage of features, installation, usage, integration, licensing, and contribution
- Easy discoverability and onboarding for new users and contributors


## Workflow

### 1. Analyze the Component
- Identify the main purpose, features, and unique selling points
- List all supported PHP versions and dependencies
- Note any special integration points (PSR compliance, service providers, etc)
- Gather usage examples, API highlights, and advanced scenarios
- Detect if FAQ, versioning, or comparison with other libs is relevant

### 2. Plan README Structure
- Use the recommended section order and headings (see below)
- Include all sections, even if some are brief ("Not applicable" if truly not relevant)
- Add badges for PHP version, license, CI, coverage, downloads, code quality, etc
- Use consistent emoji and formatting for feature lists and section headers
- Add a FAQ section if the lib has common questions or edge cases
- Add a Versioning/Breaking Changes section if the lib has relevant history
- Add a table of comparison if the lib compete with others in the ecosystem

### 3. Write Content for Each Section (with Examples)
- **Project Title and Short Description**
  
	Example:
	```markdown
	# FastForward\ComponentName
	A modern PHP library for ...
	```

- **Badges**
  
	Example:
	```markdown
	[![PHP Version](https://img.shields.io/badge/php-^8.3-blue.svg)](https://www.php.net/releases/)
	[![License](https://img.shields.io/github/license/php-fast-forward/component)](LICENSE)
	[![CI](https://github.com/php-fast-forward/component/actions/workflows/tests.yml/badge.svg)](...)
	[![Coverage](https://img.shields.io/codecov/c/github/php-fast-forward/component)](...)
	[![Downloads](https://img.shields.io/packagist/dt/php-fast-forward/component)](...)
	```

- **Overview/Features**
  
	Example:
	```markdown
	## ✨ Features
	- 🚀 Modern PHP 8.3+ syntax
	- 🔌 PSR-11/PSR-7 compliant
	- ...
	```

- **Installation**
  
	Example:
	```markdown
	## 📦 Installation
	```bash
	composer require fast-forward/component
	```
	Requirements: PHP 8.3+, ...
	```

- **Usage**
  
	Example:
	```markdown
	## 🛠️ Usage
	```php
	use FastForward\Component\Class;
	$obj = new Class(...);
	```
	```

- **API Summary**
  
	Example:
	```markdown
	## 🧰 API Summary
	| Method | Description |
	|--------|-------------|
	| get()  | Returns ... |
	| set()  | Sets ...    |
	```

- **Integration**
  
	Example:
	```markdown
	## 🔌 Integration
	Works with ...
	```

- **Directory Structure Example**
  
	Example:
	```markdown
	## 📁 Directory Structure Example
	config/
	├── app.php
	└── ...
	```

- **Advanced/Customization**
  
	Example:
	```markdown
	## ⚙️ Advanced/Customization
	How to extend, override, or customize ...
	```

- **Versioning/Breaking Changes**
  
	Example:
	```markdown
	## 🛠️ Versioning & Breaking Changes
	- v2.0: Changed ...
	- v1.0: Initial release
	```

- **FAQ**
  
	Example:
	```markdown
	## ❓ FAQ
	**Q:** How do I ...?
	**A:** ...
	```

- **License**
  
	Example:
	```markdown
	## 🛡 License
	MIT © 2026 [Author Name](https://github.com/author)
	```

- **Contributing**
  
	Example:
	```markdown
	## 🤝 Contributing
	Contributions, issues, and feature requests are welcome! ...
	```

- **Links**
  
	Example:
	```markdown
	## 🔗 Links
	- [Repository](https://github.com/php-fast-forward/component)
	- [Packagist](https://packagist.org/packages/php-fast-forward/component)
	- [RFC 2119](https://datatracker.ietf.org/doc/html/rfc2119)
	- [PSR-11](https://www.php-fig.org/psr/psr-11/)
	- [Sphinx Documentation](docs/index.rst)
	```

- **Comparison Table** (if relevant)
  
	Example:
	```markdown
	## 📊 Comparison
	| Feature | This Lib | Other Lib |
	|---------|----------|----------|
	| ...     |    ✅    |    ❌    |
	```

- **SEO & Discoverability Tips**
	- Use clear, descriptive titles and section headers
	- Add keywords relevant to the package and PHP ecosystem
	- Cross-link to related Fast Forward packages and docs

### 4. Review and Iterate
- Check for completeness: all sections present and populated
- Ensure code samples are accurate and up to date
- Validate badge links, section order, and formatting
- Use the checklist below before finalizing
- Update as the component evolves or new features are added

## Formatting & Style Guidelines
- Use emoji for section headers and feature lists for visual consistency
- Use fenced code blocks for all code and CLI examples
- Prefer tables for API summaries and comparisons
- Use Markdown links for all references
- Keep lines under 120 characters for readability

## Quick Review Checklist
- [ ] Project title and short description
- [ ] Badges (PHP version, license, CI, coverage, downloads, etc)
- [ ] Features (bulleted, with emoji)
- [ ] Installation (composer, requirements)
- [ ] Usage (basic and advanced)
- [ ] API summary (table or list)
- [ ] Integration (with other libs, PSR, frameworks)
- [ ] Directory structure (if relevant)
- [ ] Advanced/customization (if relevant)
- [ ] Versioning/breaking changes (if relevant)
- [ ] FAQ (if relevant)
- [ ] License
- [ ] Contributing
- [ ] Links (repo, packagist, docs, RFCs, PSRs)
- [ ] Comparison table (if relevant)
- [ ] Formatting and style guidelines followed
- [ ] SEO/discoverability (keywords, cross-links)
- [ ] All code samples and badges are up to date

## Prompt for Automatic Updates
> **Always update the README after any significant code, dependency, or feature change.**


## Recommended README Section Order

1. Project Title and Short Description
2. Badges (PHP version, license, CI, coverage, etc)
3. Overview/Features (bulleted, with emoji)
4. Installation (Composer command, requirements)
5. Usage (basic and advanced examples, code blocks)
6. API Summary (main classes/functions, short descriptions)
7. Integration (with other Fast Forward components, PSR, or frameworks)
8. Directory Structure Example (if relevant)
9. Advanced/Customization (optional)
10. License (MIT, with author and year)
11. Contributing (issues, PRs, links)
12. Links (Repository, Packagist, RFCs, PSRs, etc)

## Completion Criteria
- All sections above are present and populated
- Code samples are accurate and up to date
- Badges and links are valid
- README is clear for both beginners and advanced users

## Example Prompts
- "Generate a complete README.md for this Fast Forward component."
- "Update the README to add API summary and integration sections."
- "Add badges and a directory structure example to the README."

## Related Customizations
- Create a skill for CHANGELOG.md or CONTRIBUTING.md generation
- Create a skill for API reference extraction from PHPDoc
- Create a skill for cross-linking between Fast Forward packages

---

This skill is workspace-scoped and intended for maintainers and contributors of Fast Forward PHP libraries.
