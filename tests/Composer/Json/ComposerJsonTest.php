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

namespace FastForward\DevTools\Tests\Composer\Json;

use DateTimeImmutable;
use FastForward\DevTools\Composer\Json\ComposerJson;
use FastForward\DevTools\Composer\Json\Schema\Author;
use FastForward\DevTools\Composer\Json\Schema\AuthorInterface;
use FastForward\DevTools\Composer\Json\Schema\Funding;
use FastForward\DevTools\Composer\Json\Schema\Support;
use FastForward\DevTools\Composer\Json\Schema\SupportInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use UnderflowException;

use function Safe\file_put_contents;
use function Safe\json_encode;
use function Safe\tempnam;
use function Safe\unlink;

#[CoversClass(ComposerJson::class)]
#[UsesClass(Author::class)]
#[UsesClass(Funding::class)]
#[UsesClass(Support::class)]
final class ComposerJsonTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $temporaryFile) {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    /**
     * @return void
     */
    #[Test]
    public function identificationAccessorsWillReturnConfiguredData(): void
    {
        $composerJson = $this->createComposerJson([
            'name' => 'fast-forward/dev-tools',
            'description' => 'Fast Forward Development Tools',
            'version' => '1.2.3',
            'type' => 'composer-plugin',
        ]);

        self::assertSame('fast-forward/dev-tools', $composerJson->getName());
        self::assertSame('Fast Forward Development Tools', $composerJson->getDescription());
        self::assertSame('1.2.3', $composerJson->getVersion());
        self::assertSame('composer-plugin', $composerJson->getType());
    }

    /**
     * @return void
     */
    #[Test]
    public function typeWillDefaultToLibrary(): void
    {
        $composerJson = $this->createComposerJson([
            'name' => 'foo/bar',
        ]);

        self::assertSame('library', $composerJson->getType());
    }

    /**
     * @return void
     */
    #[Test]
    public function metadataAccessorsWillReturnConfiguredData(): void
    {
        $composerJson = $this->createComposerJson([
            'name' => 'fast-forward/dev-tools',
            'keywords' => ['php', 'devtools'],
            'homepage' => 'https://github.com/php-fast-forward/dev-tools',
            'readme' => 'README.md',
            'time' => '2026-01-01 10:00:00',
        ]);

        self::assertSame(['php', 'devtools'], $composerJson->getKeywords());
        self::assertSame('https://github.com/php-fast-forward/dev-tools', $composerJson->getHomepage());
        self::assertSame('README.md', $composerJson->getReadme());
        self::assertInstanceOf(DateTimeImmutable::class, $composerJson->getTime());
        self::assertSame('2026-01-01T10:00:00+00:00', $composerJson->getTime()?->format('c'));
    }

    /**
     * @return void
     */
    #[Test]
    public function metadataAccessorsWillReturnDefaultsWhenOptionalFieldsAreMissing(): void
    {
        $composerJson = $this->createComposerJson([
            'name' => 'fast-forward/dev-tools',
        ]);

        self::assertSame([], $composerJson->getKeywords());
        self::assertSame('', $composerJson->getHomepage());
        self::assertSame('', $composerJson->getReadme());
        self::assertNull($composerJson->getTime());
    }

    /**
     * @return void
     */
    #[Test]
    public function getLicenseWillReturnResolvedValue(): void
    {
        $composerJson = $this->createComposerJson([
            'license' => 'MIT',
        ]);
        self::assertSame('MIT', $composerJson->getLicense());

        $composerJson = $this->createComposerJson([
            'license' => ['MIT'],
        ]);
        self::assertSame('MIT', $composerJson->getLicense());

        $composerJson = $this->createComposerJson([
            'license' => ['MIT', 'Apache-2.0'],
        ]);
        self::assertNull($composerJson->getLicense());

        $composerJson = $this->createComposerJson([]);
        self::assertNull($composerJson->getLicense());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAuthorsWillReturnNormalizedAuthorEntries(): void
    {
        $composerJson = $this->createComposerJson([
            'authors' => [
                [
                    'name' => 'Felipe',
                    'email' => 'github@mentordosnerds.com',
                ],
            ],
        ]);

        $authors = $composerJson->getAuthors();
        self::assertIsIterable($authors);
        $authorsArray = \is_array($authors) ? $authors : iterator_to_array($authors);
        self::assertCount(1, $authorsArray);
        self::assertInstanceOf(AuthorInterface::class, $authorsArray[0]);
        self::assertSame('Felipe', $authorsArray[0]->getName());

        $firstAuthor = $composerJson->getAuthors(true);
        self::assertInstanceOf(AuthorInterface::class, $firstAuthor);
        self::assertSame('Felipe', $firstAuthor->getName());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAuthorsWithOnlyFirstAuthorWillThrowExceptionWhenNoAuthorsAreDefined(): void
    {
        $composerJson = $this->createComposerJson([]);

        $this->expectException(UnderflowException::class);
        $this->expectExceptionMessage('No author entries were declared in the Composer file.');

        $composerJson->getAuthors(true);
    }

    /**
     * @return void
     */
    #[Test]
    public function getSupportWillReturnSupportObject(): void
    {
        $composerJson = $this->createComposerJson([
            'support' => [
                'issues' => 'https://github.com/php-fast-forward/dev-tools/issues',
            ],
        ]);

        $support = $composerJson->getSupport();
        self::assertInstanceOf(SupportInterface::class, $support);
        self::assertSame('https://github.com/php-fast-forward/dev-tools/issues', $support->getIssues());
        self::assertSame('', $support->getEmail());
    }

    /**
     * @return void
     */
    #[Test]
    public function getSupportWillReturnEmptySupportObjectWhenSectionIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            'support' => 'invalid',
        ]);

        self::assertSame('', $composerJson->getSupport()->getIssues());
        self::assertSame('', $composerJson->getSupport()->getSource());
    }

    /**
     * @return void
     */
    #[Test]
    public function getFundingWillReturnFundingEntries(): void
    {
        $composerJson = $this->createComposerJson([
            'funding' => [
                [
                    'type' => 'github',
                    'url' => 'https://github.com/sponsors/mentordosnerds',
                ],
            ],
        ]);

        $funding = $composerJson->getFunding();
        self::assertIsArray($funding);
        self::assertCount(1, $funding);
        self::assertInstanceOf(Funding::class, $funding[0]);
        self::assertSame('github', $funding[0]->getType());
    }

    /**
     * @return void
     */
    #[Test]
    public function getFundingWillIgnoreInvalidEntriesAndInvalidSectionTypes(): void
    {
        $composerJson = $this->createComposerJson([
            'funding' => [
                'invalid',
                [
                    'type' => 'custom',
                    'url' => 'https://example.com/support',
                ],
                [
                    'type' => 'github',
                ],
            ],
        ]);

        $funding = $composerJson->getFunding();
        self::assertCount(2, $funding);
        self::assertSame('custom', $funding[0]->getType());
        self::assertSame('https://example.com/support', $funding[0]->getUrl());
        self::assertSame('github', $funding[1]->getType());
        self::assertSame('', $funding[1]->getUrl());

        $composerJson = $this->createComposerJson([
            'funding' => 'invalid',
        ]);
        self::assertSame([], $composerJson->getFunding());
    }

    /**
     * @return void
     */
    #[Test]
    public function getAutoloadWillReturnConfiguredMappings(): void
    {
        $composerJson = $this->createComposerJson([
            'autoload' => [
                'psr-4' => [
                    'Foo\\' => 'src/',
                ],
                'files' => ['src/functions.php'],
            ],
        ]);

        self::assertSame([
            'psr-4' => [
                'Foo\\' => 'src/',
            ],
            'files' => ['src/functions.php'],
        ], $composerJson->getAutoload());
        self::assertSame([
            'Foo\\' => 'src/',
        ], $composerJson->getAutoload('psr-4'));
        self::assertSame([], $composerJson->getAutoload('invalid'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAutoloadWillReturnEmptyArrayWhenSectionOrRequestedMappingIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            'autoload' => 'invalid',
        ]);
        self::assertSame([], $composerJson->getAutoload());

        $composerJson = $this->createComposerJson([
            'autoload' => [
                'files' => 'src/functions.php',
            ],
        ]);
        self::assertSame([], $composerJson->getAutoload('files'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAutoloadDevWillReturnConfiguredMappings(): void
    {
        $composerJson = $this->createComposerJson([
            'autoload-dev' => [
                'psr-4' => [
                    'Foo\\Tests\\' => 'tests/',
                ],
            ],
        ]);

        self::assertSame([
            'psr-4' => [
                'Foo\\Tests\\' => 'tests/',
            ],
        ], $composerJson->getAutoloadDev());
        self::assertSame([
            'Foo\\Tests\\' => 'tests/',
        ], $composerJson->getAutoloadDev('psr-4'));
        self::assertSame([], $composerJson->getAutoloadDev('files'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAutoloadDevWillReturnEmptyArrayWhenSectionOrRequestedMappingIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            'autoload-dev' => 'invalid',
        ]);
        self::assertSame([], $composerJson->getAutoloadDev());

        $composerJson = $this->createComposerJson([
            'autoload-dev' => [
                'files' => 'tests/bootstrap.php',
            ],
        ]);
        self::assertSame([], $composerJson->getAutoloadDev('files'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getMinimumStabilityWillReturnConfiguredValue(): void
    {
        $composerJson = $this->createComposerJson([
            'minimum-stability' => 'dev',
        ]);
        self::assertSame('dev', $composerJson->getMinimumStability());

        $composerJson = $this->createComposerJson([]);
        self::assertSame('stable', $composerJson->getMinimumStability());
    }

    /**
     * @return void
     */
    #[Test]
    public function getConfigWillReturnValuesFromConfigSection(): void
    {
        $composerJson = $this->createComposerJson([
            'config' => [
                'vendor-dir' => 'custom-vendor',
                'preferred-install' => [
                    '*' => 'dist',
                ],
                'process-timeout' => 300,
            ],
        ]);

        self::assertSame([
            'vendor-dir' => 'custom-vendor',
            'preferred-install' => [
                '*' => 'dist',
            ],
            'process-timeout' => 300,
        ], $composerJson->getConfig(null));

        self::assertSame('custom-vendor', $composerJson->getConfig('vendor-dir'));
        self::assertSame([
            '*' => 'dist',
        ], $composerJson->getConfig('preferred-install'));
        self::assertSame('300', $composerJson->getConfig('process-timeout'));
        self::assertSame('', $composerJson->getConfig('non-existent'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getScriptsWillReturnScriptsSection(): void
    {
        $composerJson = $this->createComposerJson([
            'scripts' => [
                'test' => 'phpunit',
            ],
        ]);

        self::assertSame([
            'test' => 'phpunit',
        ], $composerJson->getScripts());
    }

    /**
     * @return void
     */
    #[Test]
    public function getScriptsWillReturnEmptyArrayWhenSectionIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            'scripts' => 'phpunit',
        ]);

        self::assertSame([], $composerJson->getScripts());
    }

    /**
     * @return void
     */
    #[Test]
    public function getExtraWillReturnExtraSectionOrSpecificKey(): void
    {
        $composerJson = $this->createComposerJson([
            'extra' => [
                'foo' => [
                    'bar' => 'baz',
                ],
            ],
        ]);

        self::assertSame([
            'foo' => [
                'bar' => 'baz',
            ],
        ], $composerJson->getExtra());
        self::assertSame([
            'bar' => 'baz',
        ], $composerJson->getExtra('foo'));
        self::assertSame([], $composerJson->getExtra('invalid'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getExtraWillReturnEmptyArrayWhenSectionOrRequestedValueIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            'extra' => 'invalid',
        ]);
        self::assertSame([], $composerJson->getExtra());

        $composerJson = $this->createComposerJson([
            'extra' => [
                'foo' => 'bar',
            ],
        ]);
        self::assertSame([], $composerJson->getExtra('foo'));
    }

    /**
     * @return void
     */
    #[Test]
    public function getBinWillReturnConfiguredBinaries(): void
    {
        $composerJson = $this->createComposerJson([
            'bin' => 'bin/tool',
        ]);
        self::assertSame('bin/tool', $composerJson->getBin());

        $composerJson = $this->createComposerJson([
            'bin' => ['bin/tool1', 'bin/tool2'],
        ]);
        self::assertSame(['bin/tool1', 'bin/tool2'], $composerJson->getBin());
    }

    /**
     * @return void
     */
    #[Test]
    public function getBinWillFilterInvalidEntriesAndReturnEmptyArrayForInvalidSections(): void
    {
        $composerJson = $this->createComposerJson([
            'bin' => ['bin/tool1', 42, 'bin/tool2'],
        ]);
        self::assertSame(['bin/tool1', 'bin/tool2'], $composerJson->getBin());

        $composerJson = $this->createComposerJson([
            'bin' => 42,
        ]);
        self::assertSame([], $composerJson->getBin());
    }

    /**
     * @return void
     */
    #[Test]
    public function getSuggestWillReturnPackageSuggestions(): void
    {
        $composerJson = $this->createComposerJson([
            'suggest' => [
                'foo/bar' => 'For extra features',
            ],
        ]);

        self::assertSame([
            'foo/bar' => 'For extra features',
        ], $composerJson->getSuggest());
    }

    /**
     * @return void
     */
    #[Test]
    public function getSuggestWillIgnoreInvalidEntriesAndInvalidSections(): void
    {
        $composerJson = $this->createComposerJson([
            'suggest' => [
                'foo/bar' => 'For extra features',
                'foo/baz' => ['invalid'],
            ],
        ]);
        self::assertSame([
            'foo/bar' => 'For extra features',
        ], $composerJson->getSuggest());

        $composerJson = $this->createComposerJson([
            'suggest' => 'invalid',
        ]);
        self::assertSame([], $composerJson->getSuggest());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommentsWillReturnCommentData(): void
    {
        $composerJson = $this->createComposerJson([
            '_comment' => 'This is a comment',
        ]);
        self::assertSame(['This is a comment'], $composerJson->getComments());

        $composerJson = $this->createComposerJson([
            '_comment' => ['Comment 1', 'Comment 2'],
        ]);
        self::assertSame(['Comment 1', 'Comment 2'], $composerJson->getComments());
    }

    /**
     * @return void
     */
    #[Test]
    public function getCommentsWillReturnEmptyArrayWhenSectionIsInvalid(): void
    {
        $composerJson = $this->createComposerJson([
            '_comment' => 42,
        ]);

        self::assertSame([], $composerJson->getComments());
    }

    /**
     * @param array<string, mixed> $contents
     *
     * @return ComposerJson
     */
    private function createComposerJson(array $contents): ComposerJson
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'composer-json-');
        $this->temporaryFiles[] = $temporaryFile;

        file_put_contents($temporaryFile, json_encode($contents, \JSON_THROW_ON_ERROR));

        return new ComposerJson($temporaryFile);
    }
}
