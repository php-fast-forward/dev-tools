<?php

declare(strict_types=1);

/**
 * This file is part of fast-forward/dev-tools.
 *
 * This source file is subject to the license bundled
 * with this source code in the file LICENSE.
 *
 * @copyright Copyright (c) 2026 Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @see       https://github.com/php-fast-forward/dev-tools
 * @see       https://github.com/php-fast-forward
 * @see       https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\GitAttributes;

/**
 * Provides the canonical list of candidate paths for export-ignore rules.
 *
 * This class defines the baseline set of files and directories that should
 * typically be excluded from Composer package archives. The list is organized
 * into folders and files groups for deterministic ordering.
 */
final class CandidateProvider implements CandidateProviderInterface
{
    /**
     * @return list<string> Folders that are candidates for export-ignore
     */
    public function folders(): array
    {
        return [
            '/.changeset/',
            '/.circleci/',
            '/.devcontainer/',
            '/.github/',
            '/.gitlab/',
            '/.idea/',
            '/.php-cs-fixer.cache/',
            '/.vscode/',
            '/benchmarks/',
            '/build/',
            '/coverage/',
            '/docker/',
            '/docs/',
            '/examples/',
            '/fixtures/',
            '/migrations/',
            '/scripts/',
            '/src-dev/',
            '/stubs/',
            '/tests/',
            '/tools/',
        ];
    }

    /**
     * @return list<string> Files that are candidates for export-ignore
     */
    public function files(): array
    {
        return [
            '/.dockerignore',
            '/.editorconfig',
            '/.env',
            '/.env.dist',
            '/.env.example',
            '/.gitattributes',
            '/.gitignore',
            '/.gitmodules',
            '/.gitlab-ci.yml',
            '/.php-cs-fixer.dist.php',
            '/.php-cs-fixer.php',
            '/.phpunit.result.cache',
            '/.styleci.yml',
            '/.travis.yml',
            '/AGENTS.md',
            '/CODE_OF_CONDUCT.md',
            '/CONTRIBUTING.md',
            '/Dockerfile',
            '/GEMINI.md',
            '/Governance.md',
            '/Makefile',
            '/README.md',
            '/SECURITY.md',
            '/SUPPORT.md',
            '/UPGRADE.md',
            '/UPGRADING.md',
            '/Vagrantfile',
            '/bitbucket-pipelines.yml',
            '/codecov.yml',
            '/composer-normalize.json',
            '/composer-require-checker.json',
            '/context7.json',
            '/docker-compose.override.yml',
            '/docker-compose.yaml',
            '/docker-compose.yml',
            '/docker-bake.hcl',
            '/docker-stack.yml',
            '/docker-stack.yaml',
            '/ecs.php',
            '/grumphp.yml',
            '/grumphp.yml.dist',
            '/infection.json',
            '/infection.json.dist',
            '/makefile',
            '/phpbench.json',
            '/phpbench.json.dist',
            '/phpcs.xml',
            '/phpcs.xml.dist',
            '/phpmd.xml',
            '/phpmd.xml.dist',
            '/phpstan-baseline.neon',
            '/phpstan-bootstrap.php',
            '/phpstan.neon',
            '/phpstan.neon.dist',
            '/phpunit.xml.dist',
            '/psalm-baseline.xml',
            '/psalm.xml',
            '/psalm.xml.dist',
            '/rector.php',
            '/renovate.json',
            '/renovate.json5',
        ];
    }

    /**
     * Returns all candidates as a combined list with folders first, then files.
     *
     * @return list<string> All candidates in deterministic order
     */
    public function all(): array
    {
        return [...$this->folders(), ...$this->files()];
    }
}
