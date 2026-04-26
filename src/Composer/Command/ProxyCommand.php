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

namespace FastForward\DevTools\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Adapts migrated Symfony commands to Composer's BaseCommand contract.
 */
final class ProxyCommand extends BaseCommand
{
    /**
     * @param Command $command the Symfony command adapted for Composer plugin execution
     */
    public function __construct(
        private readonly Command $command,
    ) {
        parent::__construct($this->command->getName());

        $this
            ->setAliases($this->command->getAliases())
            ->setDescription($this->command->getDescription())
            ->setHelp($this->command->getHelp())
            ->setDefinition(clone $this->command->getDefinition())
            ->setHidden($this->command->isHidden());
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->command->run($input, $output);
    }
}
