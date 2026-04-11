# README Examples

Real examples from Fast Forward packages showing correct structure and formatting.

## dev-tools (Meta Package)

```markdown
# FastForward\DevTools
A Composer plugin that aggregates multiple PHP development tools into a single unified command.

[![PHP Version](https://img.shields.io/badge/php-^8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fdev--tools-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/dev-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/dev-tools/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/dev-tools/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/coverage/index.html)
[![Docs](https://img.shields.io/github/deployments/php-fast-forward/dev-tools/github-pages?logo=readthedocs&logoColor=white&label=docs&labelColor=1E293B&color=38BDF8&style=flat)](https://php-fast-forward.github.io/dev-tools/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/dev-tools?color=64748B)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/php-fast-forward?logo=githubsponsors&logoColor=white&color=EC4899)](https://github.com/sponsors/php-fast-forward)

## ✨ Features
- 🚀 Modern PHP 8.5+ syntax
- 🔌 PSR-12 compliant code style
- 🧪 Integrated testing with PHPUnit
- 📚 Automated documentation generation
- 🔄 Git hooks with GrumPHP

## 📦 Installation
```bash
composer require --dev fast-forward/dev-tools:dev-main
```

Requirements: PHP 8.5+, Composer

## 🛠️ Usage
```php
use FastForward\DevTools\Composer\Plugin;
// Plugin automatically registers commands
```
Run `composer dev-tools` for full suite.

## 🧰 API Summary
| Command | Description |
|---------|-------------|
| `dev-tools` | Run all checks |
| `dev-tools:fix` | Auto-fix style issues |
| `dev-tools tests` | Run PHPUnit |
| `dev-tools code-style` | ECS + normalize |

## 🔌 Integration
- PHPUnit 12.x
- Rector 2.x
- Easy Coding Standard
- phpDocumentor
- GrumPHP

## 🤝 Contributing
Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md).

## 🔗 Links
- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [Issues](https://github.com/php-fast-forward/dev-tools/issues)
```

## PSR-7 Middleware Package

```markdown
# FastForward\Http\Message
PSR-7 HTTP Message implementation and PSR-15 middleware.

[![PHP Version](https://img.shields.io/badge/php-^8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fhttp--message-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/http-message)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/http-message/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/http-message/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/http-message/coverage/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/http-message?color=64748B)](LICENSE)

[![PSR-7](https://img.shields.io/badge/PSR--7-http--message-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-7/)
[![PSR-15](https://img.shields.io/badge/PSR--15-http--handler-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-15/)
[![PSR-17](https://img.shields.io/badge/PSR--17-http--factory-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-17/)

## ✨ Features
- 🎯 Full PSR-7 compliance
- 🔧 PSR-17 factory implementations
- 📦 PSR-15 middleware support
- ⚡ Zero external runtime dependencies

## 📦 Installation
```bash
composer require fast-forward/http-message
```

Requirements: PHP 8.5+

## 🛠️ Usage
```php
use FastForward\Http\Message\Request;
$request = new Request('GET', 'https://example.com');
```

## 🔗 Links
- [PSR-7](https://www.php-fig.org/psr/psr-7/)
- [PSR-15](https://www.php-fig.org/psr/psr-15/)
- [PSR-17](https://www.php-fig.org/psr/psr-17/)
```

## Key Patterns

1. **Title format**: `# FastForward\ComponentName` - matches PSR-4 namespace
2. **Badge order**: Core row first, standards row for PSR packages
3. **Features**: Bullet list with emoji, sorted by importance
4. **Installation**: Single composer command, PHP version requirement
5. **Usage**: Code block with minimal viable example
6. **API Summary**: Table format for classes/methods
7. **Integration**: Key dependencies and standards
8. **Links**: Repository, Packagist, RFCs/PSRs at minimum