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

namespace FastForward\DevTools\Tests\Reflection;

use FastForward\DevTools\Console\Command\SelfUpdateCommand;
use FastForward\DevTools\Reflection\ClassReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[CoversClass(ClassReflection::class)]
final class ClassReflectionTest extends TestCase
{
    /**
     * @return void
     */
    #[Test]
    public function isInstantiableSubclassOfWillReturnTrueForMatchingClass(): void
    {
        self::assertTrue(ClassReflection::isInstantiableSubclassOf(SelfUpdateCommand::class, Command::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function isInstantiableSubclassOfWillReturnFalseForNonMatchingClass(): void
    {
        self::assertFalse(ClassReflection::isInstantiableSubclassOf(self::class, Command::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAttributeArgumentsWillReturnArgumentsForMatchingAttribute(): void
    {
        self::assertSame([
            'name' => 'dev-tools:self-update',
            'description' => 'Updates the installed fast-forward/dev-tools package.',
            'aliases' => ['self-update', 'selfupdate'],
            'hidden' => false,
            'help' => null,
            'usages' => [],
        ], ClassReflection::getAttributeArguments(SelfUpdateCommand::class, AsCommand::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAttributeArgumentsWillReturnNullWhenAttributeDoesNotExist(): void
    {
        self::assertNull(ClassReflection::getAttributeArguments(self::class, AsCommand::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function getAttributeArgumentsWillNormalizePositionalArguments(): void
    {
        self::assertSame([
            'name' => 'fixture',
            'description' => 'Fixture command.',
            'aliases' => ['alias'],
            'hidden' => false,
            'help' => null,
            'usages' => [],
        ], ClassReflection::getAttributeArguments(FixtureCommandWithPositionalAttribute::class, AsCommand::class));
    }
}

#[AsCommand('fixture', 'Fixture command.', ['alias'])]
final class FixtureCommandWithPositionalAttribute extends Command {}
