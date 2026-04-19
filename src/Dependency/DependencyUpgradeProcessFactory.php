<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * This file is part of fast-forward/dev-tools project.
 *
 * @author   Felipe Sayão Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/
 * @see      https://github.com/php-fast-forward/dev-tools
 * @see      https://github.com/php-fast-forward/dev-tools/issues
 * @see      https://php-fast-forward.github.io/dev-tools/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

namespace FastForward\DevTools\Dependency;

use FastForward\DevTools\Process\ProcessBuilderInterface;

/**
 * Creates the dependency upgrade workflow backed by Rector Jack and Composer.
 */
final readonly class DependencyUpgradeProcessFactory implements DependencyUpgradeProcessFactoryInterface
{
    /**
     * @param ProcessBuilderInterface $processBuilder the builder used to assemble CLI processes
     */
    public function __construct(
        private ProcessBuilderInterface $processBuilder,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function create(bool $fix, bool $dev): array
    {
        $openVersionsBuilder = $this->processBuilder;

        if ($dev) {
            $openVersionsBuilder = $openVersionsBuilder->withArgument('--dev');
        }

        if (! $fix) {
            return [
                $openVersionsBuilder
                    ->withArgument('--dry-run')
                    ->build('vendor/bin/jack open-versions'),
                $this->processBuilder
                    ->withArgument('--dry-run')
                    ->build('vendor/bin/jack raise-to-installed'),
            ];
        }

        return [
            $openVersionsBuilder->build('vendor/bin/jack open-versions'),
            $this->processBuilder->build('vendor/bin/jack raise-to-installed'),
            $this->processBuilder
                ->withArgument('-W')
                ->withArgument('--no-progress')
                ->build('composer update'),
        ];
    }
}
