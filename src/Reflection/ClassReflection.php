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
 *
 * This helper keeps command discovery code focused on command behavior instead of
 * raw reflection boilerplate.
 */
final class ClassReflection
{
    /**
     * Detects whether a class can be instantiated as a subclass of another class.
     *
     * @param class-string $className the class being inspected
     * @param class-string $parentClass the required parent class or interface
     *
     * @return bool true when the class is instantiable and extends or implements the expected parent
     */
    public static function isInstantiableSubclassOf(string $className, string $parentClass): bool
    {
        $reflection = new ReflectionClass($className);

        return $reflection->isInstantiable() && $reflection->isSubclassOf($parentClass);
    }

    /**
     * Returns the first matching attribute arguments normalized by constructor parameter name.
     *
     * Positional arguments are mapped to their constructor parameter names so callers do not
     * need to understand how the attribute was declared at the call site.
     *
     * @param class-string $className the class being inspected
     * @param class-string $attributeClass the attribute class being read
     *
     * @return array<string, mixed>|null the normalized argument map, or null when the attribute is absent
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
