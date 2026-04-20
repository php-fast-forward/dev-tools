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

namespace FastForward\DevTools\Tests\Console\Command;

use FastForward\DevTools\Composer\Json\ComposerJsonInterface;
use FastForward\DevTools\Console\Command\UpdateComposerJsonCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Resource\FileDiff;
use FastForward\DevTools\Resource\FileDiffer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function Safe\json_decode;

#[CoversClass(UpdateComposerJsonCommand::class)]
#[UsesClass(FileDiff::class)]
final class UpdateComposerJsonCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $composer;

    private ObjectProphecy $fileLocator;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileDiffer;

    private ObjectProphecy $questionHelper;

    private UpdateComposerJsonCommand $command;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->composer = $this->prophesize(ComposerJsonInterface::class);
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->fileLocator = $this->prophesize(FileLocatorInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->questionHelper = $this->prophesize(QuestionHelper::class);
        $this->output->isDecorated()
            ->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->fileDiffer->formatForConsole(Argument::cetera())
            ->willReturn(null);
        $this->questionHelper->getName()
            ->willReturn('question');
        $this->questionHelper->setHelperSet(Argument::type(HelperSet::class))
            ->shouldBeCalled();
        $this->input->getOption('dry-run')
            ->willReturn(false);
        $this->input->getOption('check')
            ->willReturn(false);
        $this->input->getOption('interactive')
            ->willReturn(false);
        $this->input->isInteractive()
            ->willReturn(false);

        $this->command = new UpdateComposerJsonCommand(
            $this->composer->reveal(),
            $this->filesystem->reveal(),
            $this->fileLocator->reveal(),
            $this->fileDiffer->reveal(),
        );
        $this->command->setHelperSet(new HelperSet([
            'question' => $this->questionHelper->reveal(),
        ]));
    }

    /**
     * @return void
     */
    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('update-composer-json', $this->command->getName());
        self::assertSame(
            'Updates composer.json with Fast Forward dev-tools scripts and metadata.',
            $this->command->getDescription()
        );
        self::assertSame(
            'This command adds or updates composer.json scripts and GrumPHP extra configuration required by dev-tools.',
            $this->command->getHelp()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillUpdateComposerJsonScriptsAndExtraConfiguration(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(
            'generated dev-tools composer.json configuration',
            '/app/composer.json',
            Argument::type('string'),
            '{"name":"example/package"}',
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
            '@@ diff @@'
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            '/app/composer.json',
            Argument::that(static fn(string $contents): bool => str_contains($contents, '"dev-tools"')
                && str_contains($contents, '"grumphp"')),
        )->shouldBeCalledOnce();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillAddReadmeMetadataWhenReadmeExistsAndComposerJsonDoesNotDeclareReadme(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(true);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(
            'generated dev-tools composer.json configuration',
            '/app/composer.json',
            Argument::type('string'),
            '{"name":"example/package"}',
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            '/app/composer.json',
            Argument::that(static function (string $contents): bool {
                $composerJson = json_decode($contents, true);

                return 'README.md' === $composerJson['readme'];
            }),
        )->shouldBeCalledOnce();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillPreserveExistingReadmeMetadata(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package","readme":"docs/readme.md"}');
        $this->composer->getReadme()
            ->willReturn('docs/readme.md');
        $this->filesystem->exists('README.md', '/app')
            ->shouldNotBeCalled();
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(
            'generated dev-tools composer.json configuration',
            '/app/composer.json',
            Argument::type('string'),
            '{"name":"example/package","readme":"docs/readme.md"}',
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            '/app/composer.json',
            Argument::that(static function (string $contents): bool {
                $composerJson = json_decode($contents, true);

                return 'docs/readme.md' === $composerJson['readme'];
            }),
        )->shouldBeCalledOnce();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipReadmeMetadataWhenReadmeDoesNotExist(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(
            'generated dev-tools composer.json configuration',
            '/app/composer.json',
            Argument::type('string'),
            '{"name":"example/package"}',
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        )->willReturn(new FileDiff(
            FileDiff::STATUS_CHANGED,
            'Updating managed file /app/composer.json from generated dev-tools composer.json configuration.',
        ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            '/app/composer.json',
            Argument::that(static function (string $contents): bool {
                $composerJson = json_decode($contents, true);

                return ! \array_key_exists('readme', $composerJson);
            }),
        )->shouldBeCalledOnce();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWhenComposerFileDoesNotExist(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(false);
        $this->filesystem->readFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessWithoutWritingWhenComparisonIsUnchanged(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_UNCHANGED,
                'composer.json is already synchronized.',
            ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnFailureInCheckModeWhenComposerJsonWouldChange(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->input->getOption('check')
            ->willReturn(true);
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'composer.json must be updated.',
                '@@ diff @@',
            ))->shouldBeCalledOnce();
        $this->fileDiffer->formatForConsole('@@ diff @@', false)
            ->willReturn('@@ diff @@')
            ->shouldBeCalledOnce();
        $this->output->writeln('@@ diff @@')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(UpdateComposerJsonCommand::FAILURE, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillReturnSuccessInDryRunModeWhenComposerJsonWouldChange(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->input->getOption('dry-run')
            ->willReturn(true);
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'composer.json must be updated.',
            ))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return void
     */
    #[Test]
    public function executeWillSkipWritingWhenInteractiveConfirmationIsDeclined(): void
    {
        $this->input->getOption('file')
            ->willReturn('/app/composer.json');
        $this->input->getOption('interactive')
            ->willReturn(true);
        $this->input->isInteractive()
            ->willReturn(true);
        $this->filesystem->exists('/app/composer.json')
            ->willReturn(true);
        $this->filesystem->readFile('/app/composer.json')
            ->willReturn('{"name":"example/package"}');
        $this->composer->getReadme()
            ->willReturn('');
        $this->filesystem->exists('README.md', '/app')
            ->willReturn(false);
        $this->fileLocator->locate('grumphp.yml', Argument::type('string'))
            ->willReturn('/app/vendor/fast-forward/dev-tools/grumphp.yml');
        $this->fileDiffer->diffContents(Argument::cetera())
            ->willReturn(new FileDiff(
                FileDiff::STATUS_CHANGED,
                'composer.json must be updated.',
            ))->shouldBeCalledOnce();
        $this->questionHelper->ask(
            $this->input->reveal(),
            $this->output->reveal(),
            Argument::type(ConfirmationQuestion::class),
        )->willReturn(false)
            ->shouldBeCalledOnce();
        $this->output->writeln('<comment>Skipped updating /app/composer.json.</comment>')
            ->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(UpdateComposerJsonCommand::SUCCESS, $this->executeCommand());
    }

    /**
     * @return int
     */
    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
