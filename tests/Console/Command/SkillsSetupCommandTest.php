<?php

declare(strict_types=1);

/**
 * Fast Forward Development Tools for PHP projects.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace FastForward\DevTools\Tests\Console\Command;

use Composer\InstalledVersions;
use FastForward\DevTools\Console\Command\SkillsSetupCommand;
use FastForward\DevTools\Filesystem\Filesystem;
use FastForward\DevTools\Filesystem\FinderFactory;
use FastForward\DevTools\Process\ProcessBuilderInterface;
use FastForward\DevTools\Process\ProcessQueueInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Symfony\Component\Process\Process;

use function Safe\chdir;
use function Safe\file_get_contents;
use function Safe\getcwd;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\realpath;

#[CoversClass(SkillsSetupCommand::class)]
#[UsesClass(Filesystem::class)]
#[UsesClass(FinderFactory::class)]
final class SkillsSetupCommandTest extends TestCase
{
    use ProphecyTrait;

    private SymfonyFilesystem $filesystem;

    private string $workspace;

    private string $previousDirectory;

    private array $commands = [];

    private ?Process $queued = null;

    private CommandTester $tester;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->filesystem = new SymfonyFilesystem();
        $this->previousDirectory = getcwd();
        $this->workspace = sys_get_temp_dir() . '/skills-setup-' . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($this->workspace);
        $this->workspace = realpath($this->workspace);
        chdir($this->workspace);
        $this->writeManifest();
        $this->filesystem->dumpFile('runner.php', <<<'PHP'
            <?php
            $arguments = array_slice($argv, 1);
            file_put_contents('calls.jsonl', json_encode($arguments) . "\n", FILE_APPEND);
            if (is_file('fail') && in_array(file_get_contents('fail'), $arguments, true)) {
                exit(23);
            }
            if (in_array('skills:init', $arguments, true)) {
                file_put_contents('skills.json', json_encode(['sources' => [], 'aliases' => []]));
            }
            if (in_array('skills:add', $arguments, true)) {
                $offset = array_search('skills:add', $arguments, true);
                $name = $arguments[$offset + 1];
                $config = json_decode(file_get_contents('skills.json'), true);
                $config['fixture-sources'][$name] = $arguments;
                file_put_contents('skills.json', json_encode($config));
            }
            PHP);
        $builder = $this->prophesize(ProcessBuilderInterface::class);
        $test = $this;
        $builder->build(Argument::type('array'))->will(static function (array $arguments) use ($test): Process {
            $test->commands[] = $arguments[0];

            return new Process([\PHP_BINARY, $test->workspace . '/runner.php', ...$arguments[0]]);
        });
        $queue = $this->prophesize(ProcessQueueInterface::class);
        $queue->add(Argument::type(Process::class), false, false, Argument::type('string'))
            ->will(static function (array $arguments) use ($test): void {
                $test->queued = $arguments[0];
            });
        $queue->run(Argument::type(OutputInterface::class))->will(static fn(): int => $test->queued->run());
        $this->tester = new CommandTester(new SkillsSetupCommand(
            $builder->reveal(),
            $queue->reveal(),
            new Filesystem(),
            new FinderFactory(),
        ));
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        chdir($this->previousDirectory);
        $this->filesystem->remove($this->workspace);
    }

    /**
     * @return void
     */
    #[Test]
    public function externalSetupWillInstallOnlySkillsAndUseOfficialCliWithPinnedSources(): void
    {
        self::assertSame(0, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertSame([
            ['composer', '--no-plugins', '--no-scripts', 'config', 'allow-plugins.llm/skills', 'true'],
            ['composer', 'require', '--dev', 'llm/skills:^1.15', '--no-plugins', '--no-scripts', '--no-interaction'],
            [
                'composer',
                'skills:init',
                '--quick',
                '--no-sync',
                '--target=.agents/skills',
                '--trust=fast-forward/*',
                '--auto-sync',
                '--no-discovery',
                '--no-interaction',
            ],
            ['composer', 'skills:add', 'php-fast-forward/dev-tools', '--ref=v2.0.0', '--no-sync', '--no-interaction'],
            ['composer', 'skills:add', 'php-fast-forward/changelog', '--ref=^0.4.0', '--no-sync', '--no-interaction'],
            ['composer', 'skills:update', '--no-interaction'],
        ], $this->commands);
        self::assertCount(6, file('calls.jsonl'));
    }

    /**
     * @return void
     */
    #[Test]
    public function dryRunWillNotExecuteComposerOrCreateConfiguration(): void
    {
        $before = file_get_contents('composer.json');
        self::assertSame(0, $this->tester->execute([
            '--dry-run' => true,
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertSame($before, file_get_contents('composer.json'));
        self::assertFileDoesNotExist('skills.json');
        self::assertFileDoesNotExist('calls.jsonl');
        self::assertStringContainsString('skills:init', $this->tester->getDisplay());
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function localPayloads(): iterable
    {
        yield 'missing directory' => [null];
        yield 'empty directory' => [''];
        yield 'unrelated file only' => ['README.md'];
        yield 'root skill' => ['SKILL.md'];
        yield 'nested skill' => ['nested/skill/SKILL.md'];
    }

    /**
     * @param string|null $payload
     *
     * @return void
     */
    #[Test]
    #[DataProvider('localPayloads')]
    public function selfSetupWillRegisterOnlyDirectoriesContainingSkillPayloads(?string $payload): void
    {
        $this->writeManifest('fast-forward/dev-tools');
        if (null !== $payload) {
            $this->filesystem->mkdir('resources/skills');
            if ('' !== $payload) {
                $this->filesystem->dumpFile('resources/skills/' . $payload, 'payload');
            }
        }

        self::assertSame(0, $this->tester->execute([]));
        $local = array_values(
            array_filter($this->commands, static fn(array $command): bool => \in_array(
                './resources/skills',
                $command,
                true
            ))
        );
        self::assertCount(null !== $payload && str_ends_with($payload, 'SKILL.md') ? 1 : 0, $local);
        self::assertStringNotContainsString('php-fast-forward/dev-tools', $this->tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function repeatSetupWillPreserveExistingPolicyAndAvoidReinitializationOrReinstallation(): void
    {
        $this->writeManifest('fixture/consumer', [
            'require-dev' => [
                'llm/skills' => '^1.15',
            ],
            'config' => [
                'allow-plugins' => [
                    'other/plugin' => true,
                    'llm/skills' => true,
                ],
            ],
        ]);
        $this->filesystem->dumpFile('vendor/llm/skills/composer.json', '{}');
        $policy = [
            'target' => '.custom/skills',
            'aliases' => ['.alias/skills'],
            'discovery' => true,
            'dependencies' => [
                'composer' => [
                    'trusted' => ['custom/*'],
                ],
            ],
            'sources' => [[
                'from' => 'github',
                'package' => 'custom/repo',
                'ref' => 'v1',
            ]],
        ];
        $this->filesystem->dumpFile('skills.json', json_encode($policy));
        $manifestBefore = file_get_contents('composer.json');

        self::assertSame(0, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
            '--no-sync' => true,
        ]));
        $first = file_get_contents('skills.json');
        self::assertSame(0, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
            '--no-sync' => true,
        ]));
        self::assertSame($first, file_get_contents('skills.json'));
        self::assertSame($manifestBefore, file_get_contents('composer.json'));
        $actual = json_decode($first, true);
        unset($actual['fixture-sources']);
        self::assertSame($policy, $actual);
        self::assertCount(4, $this->commands);
        self::assertStringNotContainsString('skills:init', $this->tester->getDisplay());
    }

    /**
     * @return void
     */
    #[Test]
    public function failureWillStopBeforeAnyLaterSetupCommands(): void
    {
        $this->filesystem->dumpFile('fail', 'require');

        self::assertSame(1, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertCount(2, $this->commands);
        self::assertFileDoesNotExist('skills.json');
    }

    /**
     * @return void
     */
    #[Test]
    public function explicitPluginDenialWillFailBeforeAnyMutation(): void
    {
        $this->writeManifest('fixture/consumer', [
            'config' => [
                'allow-plugins' => [
                    'llm/skills' => false,
                ],
            ],
        ]);

        self::assertSame(1, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertSame([], $this->commands);
        self::assertFileDoesNotExist('calls.jsonl');
    }

    /**
     * @return void
     */
    #[Test]
    public function automaticSourceWillUseExecutingComposerReference(): void
    {
        $reference = str_repeat('a', 40);
        $fixture = InstalledVersions::getAllRawData()[0];
        $fixture['versions']['fast-forward/dev-tools']['reference'] = $reference;
        $vendor = $this->workspace . '/installed-fixture/vendor';
        $this->filesystem->dumpFile(
            $vendor . '/composer/installed.php',
            '<?php return ' . var_export($fixture, true) . ';',
        );
        $loader = new \Composer\Autoload\ClassLoader($vendor);
        $loader->register(true);
        try {
            self::assertSame(0, $this->tester->execute([]));
            self::assertContains('--ref=' . $reference, $this->commands[3]);
        } finally {
            $loader->unregister();
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function emptyExplicitRefWillFailBeforeAnyMutation(): void
    {
        self::assertSame(1, $this->tester->execute([
            '--dev-tools-ref' => ' ',
        ]));
        self::assertSame([], $this->commands);
        self::assertFileDoesNotExist('calls.jsonl');
        self::assertFileDoesNotExist('skills.json');
    }

    /**
     * @return void
     */
    #[Test]
    public function consumerLocalSkillsWillBeRegisteredAlongsideExternalDevToolsSource(): void
    {
        $this->filesystem->dumpFile('resources/skills/SKILL.md', 'consumer skill');
        self::assertSame(0, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertContains(
            ['composer', 'skills:add', './resources/skills', '--from=dir', '--no-sync', '--no-interaction'],
            $this->commands
        );
        self::assertContains(
            ['composer', 'skills:add', 'php-fast-forward/dev-tools', '--ref=v2.0.0', '--no-sync', '--no-interaction'],
            $this->commands
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function productionDependencyWillKeepItsConstraintAndPlacementWhileInstallingMissingVendor(): void
    {
        $this->writeManifest('fixture/consumer', [
            'require' => [
                'llm/skills' => '^1.15.2',
            ],
        ]);
        $before = file_get_contents('composer.json');
        self::assertSame(0, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertContains(
            ['composer', 'install', '--no-plugins', '--no-scripts', '--no-interaction'],
            $this->commands
        );
        self::assertSame(
            [],
            array_values(array_filter(
                $this->commands,
                static fn(array $command): bool => \in_array('require', $command, true)
            ))
        );
        self::assertSame($before, file_get_contents('composer.json'));
    }

    /**
     * @return void
     */
    #[Test]
    public function arrayManifestWillFailBeforeAnyMutation(): void
    {
        $this->filesystem->dumpFile('composer.json', '[]');
        self::assertSame(1, $this->tester->execute([
            '--dev-tools-ref' => 'v2.0.0',
        ]));
        self::assertSame([], $this->commands);
        self::assertFileDoesNotExist('calls.jsonl');
    }

    /**
     * @param string $name
     * @param array<string, mixed> $extra
     *
     * @return void
     */
    private function writeManifest(string $name = 'fixture/consumer', array $extra = []): void
    {
        $this->filesystem->dumpFile('composer.json', json_encode([
            'name' => $name,
            ...$extra,
        ]));
    }
}
