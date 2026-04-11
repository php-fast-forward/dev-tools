# Badge Pattern

Use this file to keep README badges aligned with the current Fast Forward package pattern.

## Current Ecosystem Pattern

The most complete and current Fast Forward package READMEs usually start with this core badge stack:

1. `PHP Version`
2. `Composer Package`
3. `Tests`
4. `Coverage`
5. `Docs`
6. `License`
7. `GitHub Sponsors`

## Default Badge Order

Use this order unless the repository has an explicit reason to differ:

### Core Row

```markdown
[![PHP Version](https://img.shields.io/badge/php-^8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fpackage-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/package)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/package/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/package/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/package/coverage/index.html)
[![Docs](https://img.shields.io/github/deployments/php-fast-forward/package/github-pages?logo=readthedocs&logoColor=white&label=docs&labelColor=1E293B&color=38BDF8&style=flat)](https://php-fast-forward.github.io/package/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/package?color=64748B)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/php-fast-forward?logo=githubsponsors&logoColor=white&color=EC4899)](https://github.com/sponsors/php-fast-forward)
```

### Standards Row

When the package is centered on one or more PSRs, add a second row after the core row.

Examples from the ecosystem:

```markdown
[![PSR-7](https://img.shields.io/badge/PSR--7-http--message-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-7/)
[![PSR-11](https://img.shields.io/badge/PSR--11-container-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-11/)
[![PSR-14](https://img.shields.io/badge/PSR--14-event--dispatcher-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-14/)
[![PSR-17](https://img.shields.io/badge/PSR--17-http--factory-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-17/)
[![PSR-18](https://img.shields.io/badge/PSR--18-http--client-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-18/)
[![PSR-20](https://img.shields.io/badge/PSR--20-clock-777BB4?logo=php&logoColor=white)](https://www.php-fig.org/psr/psr-20/)
```

Keep standards badges focused. Add only the PSRs that are central to the package contract or are already highlighted by the repository.

## Inclusion Rules

- Prefer the `Composer Package` badge over a generic downloads badge. This is the current Fast Forward pattern for surfacing Packagist near the top of the README.
- Keep `Packagist` in the bottom links section as well. In Fast Forward READMEs, Packagist is usually visible both in the badge block and in the links section.
- Include the `Docs` badge when the package publishes GitHub Pages documentation at `https://php-fast-forward.github.io/<repo>/index.html`.
- Include the `Coverage` badge when the package publishes coverage to `https://php-fast-forward.github.io/<repo>/coverage/index.html`.
- Include the `GitHub Sponsors` badge by default for maintained Fast Forward packages unless the repository clearly avoids sponsorship messaging.
- Include standards badges only when they are materially relevant to the package.
- If a repository truly does not have tests, docs, or coverage planned, do not invent those badges. Prefer an honest smaller stack over broken links.

## Deriving Values

- Read `composer.json` to get the package name, for example `fast-forward/dev-tools`.
- Read the repository directory or git remote to get the repository slug, for example `dev-tools`.
- Use the repository slug in GitHub and GitHub Pages links.
- Use the Composer package name in Packagist links.
- For the static Composer badge label, copy the current Fast Forward badge style and replace the package segment carefully. The badge path typically uses `%2F` for `/` and doubles `-` inside the displayed package name.

## Practical Defaults

- For most package READMEs, start with the full core row.
- For PSR-focused libraries, add a standards row immediately after the core row.
- For metapackages or aggregate packages, omit standards badges unless they are part of the package's primary value proposition.
- When refreshing an older README, prefer upgrading it to the newer badge pattern rather than preserving a thinner legacy stack.
