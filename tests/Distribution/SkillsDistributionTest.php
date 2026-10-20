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

namespace FastForward\DevTools\Tests\Distribution;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

use function Safe\file_get_contents;
use function Safe\glob;
use function Safe\json_decode;

/**
 * The Composer distribution MUST expose portable skills independently of the CLI.
 *
 * @copyright 2026 Felipe Sayão Lobato Abreu
 * @license https://opensource.org/licenses/MIT MIT License
 */
#[CoversNothing]
final class SkillsDistributionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function distributionWillPublishNamespacedSkillsWithoutComposerPluginSetup(): void
    {
        $root = \dirname(__DIR__, 2);
        $composer = json_decode(file_get_contents($root . '/composer.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('resources/skills', $composer['extra']['skills']['source']);
        self::assertArrayHasKey('llm/skills', $composer['suggest']);
        self::assertSame('library', $composer['type']);
        self::assertArrayNotHasKey('class', $composer['extra']);

        $entrypoints = glob($root . '/resources/skills/*/SKILL.md');
        self::assertCount(8, $entrypoints);

        foreach ($entrypoints as $entrypoint) {
            $contents = file_get_contents($entrypoint);
            $parts = explode('---', $contents, 3);
            $metadata = Yaml::parse($parts[1]);
            $directory = \dirname($entrypoint);

            self::assertSame(basename($directory), $metadata['name']);
            self::assertStringStartsWith('fast-forward-', $metadata['name']);
            self::assertFileExists($directory . '/LICENSE');
            self::assertFalse(is_link($directory));
            self::assertStringNotContainsString('composer require --dev fast-forward/dev-tools', $contents);
            self::assertStringNotContainsString('DevTools\\Composer\\Plugin', $contents);
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function selfConsumptionWillUseCanonicalDirectoryWithoutCacheAliases(): void
    {
        $root = \dirname(__DIR__, 2);
        $config = json_decode(file_get_contents($root . '/skills.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('.agents/skills', $config['target']);
        self::assertSame([], $config['aliases']);
        self::assertFalse($config['vendor-sources']);
        self::assertContains([
            'from' => 'dir',
            'path' => 'resources/skills',
            'package' => 'fast-forward/dev-tools',
        ], $config['sources']);
    }
}
