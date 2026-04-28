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

namespace FastForward\DevTools\Reflection;

use ReflectionClass;
use ReflectionMethod;

/**
 * Centralizes small reflection lookups used by DevTools runtime metadata.
 */
final class ClassReflection
{
    /**
     * @param class-string $className
     * @param class-string $parentClass
     */
    public static function isInstantiableSubclassOf(string $className, string $parentClass): bool
    {
        $reflection = new ReflectionClass($className);

        return $reflection->isInstantiable() && $reflection->isSubclassOf($parentClass);
    }

    /**
     * @param class-string $className
     * @param class-string $attributeClass
     *
     * @return array<string, mixed>|null
     */
    public static function getAttributeArguments(string $className, string $attributeClass): ?array
    {
        $reflection = new ReflectionClass($className);
        $attribute = $reflection->getAttributes($attributeClass)[0] ?? null;

        if (null === $attribute) {
            return null;
        }

        $arguments = $attribute->getArguments();
        $constructor = new ReflectionMethod($attributeClass, '__construct');
        $normalizedArguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $normalizedArguments[$parameter->getName()] = $arguments[$parameter->getName()]
                ?? $arguments[$parameter->getPosition()]
                ?? ($parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null);
        }

        return $normalizedArguments;
    }
}
