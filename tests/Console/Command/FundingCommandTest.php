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

use FastForward\DevTools\Console\Command\FundingCommand;
use FastForward\DevTools\Filesystem\FilesystemInterface;
use FastForward\DevTools\Funding\ComposerFundingCodec;
use FastForward\DevTools\Funding\FundingProfile;
use FastForward\DevTools\Funding\FundingProfileMerger;
use FastForward\DevTools\Funding\FundingYamlCodec;
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use function Safe\json_decode;

#[CoversClass(FundingCommand::class)]
#[UsesClass(FileDiff::class)]
#[UsesClass(ComposerFundingCodec::class)]
#[UsesClass(FundingProfile::class)]
#[UsesClass(FundingProfileMerger::class)]
#[UsesClass(FundingYamlCodec::class)]
final class FundingCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $filesystem;

    private ObjectProphecy $input;

    private ObjectProphecy $output;

    private ObjectProphecy $fileDiffer;

    private FundingCommand $command;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(FilesystemInterface::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);
        $this->fileDiffer = $this->prophesize(FileDiffer::class);
        $this->output->isDecorated()->willReturn(false);
        $this->output->writeln(Argument::any());
        $this->fileDiffer->formatForConsole(Argument::cetera())->willReturn(null);
        $this->input->getOption('composer-file')->willReturn('composer.json');
        $this->input->getOption('funding-file')->willReturn('.github/FUNDING.yml');
        $this->input->getOption('dry-run')->willReturn(false);
        $this->input->getOption('check')->willReturn(false);
        $this->input->getOption('interactive')->willReturn(false);
        $this->filesystem->dirname('.github/FUNDING.yml')->willReturn('.github');

        $this->command = new FundingCommand(
            $this->filesystem->reveal(),
            new ComposerFundingCodec(),
            new FundingYamlCodec(),
            new FundingProfileMerger(),
            $this->fileDiffer->reveal(),
        );
    }

    #[Test]
    public function executeWillCreateComposerFundingFromFundingYaml(): void
    {
        $composerContents = '{"name":"example/package"}';
        $fundingYaml = "github: foo\ncustom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')->willReturn(true);
        $this->filesystem->readFile('composer.json')->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::that(static function (string $contents): bool {
                $decoded = json_decode($contents, true);

                return [
                    ['type' => 'github', 'url' => 'https://github.com/sponsors/foo'],
                    ['type' => 'custom', 'url' => 'https://example.com/support'],
                ] === $decoded['funding'];
            }),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(
            'composer.json',
            Argument::that(static fn(string $contents): bool => str_contains($contents, '"funding"')),
        )->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function executeWillCreateFundingYamlFromComposerFunding(): void
    {
        $composerContents = <<<'JSON'
{"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"},{"type":"custom","url":"https://example.com/support"}]}
JSON;

        $this->filesystem->exists('composer.json')->willReturn(true);
        $this->filesystem->readFile('composer.json')->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')->willReturn(false);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Composer unchanged'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::that(static function (string $contents): bool {
                $decoded = Yaml::parse($contents);

                return 'foo' === $decoded['github']
                    && 'https://example.com/support' === $decoded['custom'];
            }),
            null,
            'Managed file .github/FUNDING.yml will be created from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Funding changed'))->shouldBeCalledOnce();
        $this->filesystem->mkdir('.github')->shouldBeCalledOnce();
        $this->filesystem->dumpFile('.github/FUNDING.yml', Argument::type('string'))->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function executeWillMergeBothSourcesWithoutDuplicatingEntries(): void
    {
        $composerContents = <<<'JSON'
{"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"}]}
JSON;
        $fundingYaml = "custom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')->willReturn(true);
        $this->filesystem->readFile('composer.json')->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::that(static function (string $contents): bool {
                $decoded = json_decode($contents, true);

                return [
                    ['type' => 'github', 'url' => 'https://github.com/sponsors/foo'],
                    ['type' => 'custom', 'url' => 'https://example.com/support'],
                ] === $decoded['funding'];
            }),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Composer changed'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::that(static function (string $contents): bool {
                $decoded = Yaml::parse($contents);

                return 'foo' === $decoded['github']
                    && 'https://example.com/support' === $decoded['custom'];
            }),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_CHANGED, 'Funding changed'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile('composer.json', Argument::type('string'))->shouldBeCalledOnce();
        $this->filesystem->mkdir('.github')->shouldBeCalledOnce();
        $this->filesystem->dumpFile('.github/FUNDING.yml', Argument::type('string'))->shouldBeCalledOnce();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function executeWillBeIdempotentWhenFundingMetadataAlreadyMatches(): void
    {
        $composerContents = <<<'JSON'
{"name":"example/package","funding":[{"type":"github","url":"https://github.com/sponsors/foo"},{"type":"custom","url":"https://example.com/support"}]}
JSON;
        $fundingYaml = "github: foo\ncustom: https://example.com/support\n";

        $this->filesystem->exists('composer.json')->willReturn(true);
        $this->filesystem->readFile('composer.json')->willReturn($composerContents);
        $this->filesystem->exists('.github/FUNDING.yml')->willReturn(true);
        $this->filesystem->readFile('.github/FUNDING.yml')->willReturn($fundingYaml);
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            'composer.json',
            Argument::type('string'),
            $composerContents,
            'Updating managed file composer.json from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Composer unchanged'))->shouldBeCalledOnce();
        $this->fileDiffer->diffContents(
            'generated funding metadata synchronization',
            '.github/FUNDING.yml',
            Argument::type('string'),
            $fundingYaml,
            'Updating managed file .github/FUNDING.yml from generated funding metadata synchronization.',
        )->willReturn(new FileDiff(FileDiff::STATUS_UNCHANGED, 'Funding unchanged'))->shouldBeCalledOnce();
        $this->filesystem->dumpFile(Argument::cetera())->shouldNotBeCalled();

        self::assertSame(FundingCommand::SUCCESS, $this->executeCommand());
    }

    #[Test]
    public function commandWillSetExpectedNameDescriptionAndHelp(): void
    {
        self::assertSame('funding', $this->command->getName());
        self::assertSame(
            'Synchronizes funding metadata between composer.json and .github/FUNDING.yml.',
            $this->command->getDescription(),
        );
        self::assertSame(
            'This command merges supported funding entries across composer.json and .github/FUNDING.yml while preserving unsupported providers.',
            $this->command->getHelp(),
        );
    }

    private function executeCommand(): int
    {
        $reflectionMethod = new ReflectionMethod($this->command, 'execute');

        return $reflectionMethod->invoke($this->command, $this->input->reveal(), $this->output->reveal());
    }
}
