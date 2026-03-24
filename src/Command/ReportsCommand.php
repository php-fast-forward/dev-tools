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

namespace FastForward\DevTools\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\ob_start;
use function Safe\ob_get_clean;

final class ReportsCommand extends AbstractCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('reports')
            ->setDescription('Generates the frontpage for Fast Forward documentation.')
            ->setHelp(
                'This command generates the frontpage for Fast Forward documentation, including links to API documentation and test reports.'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating frontpage for Fast Forward documentation...</info>');

        $docsPath = $this->getAbsolutePath('./public/api');
        $coveragePath = $this->getAbsolutePath('./public/coverage');

        $output->writeln('<info>Generating API documentation on path: ' . $docsPath . '</info>');
        $this->runCommand('docs', ['target' => $docsPath], $output);

        $output->writeln('<info>Generating test coverage report on path: ' . $coveragePath . '</info>');
        $this->runCommand('tests', ['coverage' => $coveragePath], $output);

        $this->generateFrontpage();

        $output->writeln('<info>Frontpage generation completed!</info>');

        return self::SUCCESS;
    }

    /**
     * @return void
     */
    private function generateFrontpage(): void
    {
        $html = $this->renderTemplate(
            $this->getTitle(),
            [
                'API Documentation' => './api/index.html',
                'Testdox Report' => './coverage/testdox.html',
                'Test Coverage Report' => './coverage/index.html',
            ]
        );

        $this->filesystem->dumpFile($this->getAbsolutePath('./public/index.html'), $html);
    }

    /**
     * @param string $title
     * @param array $links
     *
     * @return string
     */
    private function renderTemplate(string $title, array $links): string
    {
        ob_start();
        extract([
            'title' => $title,
            'links' => $links,
        ]);
        include $this->getAbsolutePath('./resources/index.php');

        return ob_get_clean();
    }
}
