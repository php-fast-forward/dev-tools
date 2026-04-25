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

namespace FastForward\DevTools\Console\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapts migrated Symfony commands to Composer's BaseCommand contract.
 */
final class ProxyCommand extends BaseCommand
{
    public function __construct(
        private readonly Command $command,
    ) {
        parent::__construct($command->getName());

        $this->setAliases($command->getAliases());
        $this->setDescription($command->getDescription());
        $this->setHelp($command->getHelp());
        $this->setDefinition(clone $command->getDefinition());
        $this->setHidden($command->isHidden());
        $this->setIgnoreValidationErrors($command->ignoreValidationErrors());
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->command->setApplication($this->getApplication());
        $this->command->setHelperSet($this->getHelperSet());

        return $this->command->run($input, $output);
    }
}
