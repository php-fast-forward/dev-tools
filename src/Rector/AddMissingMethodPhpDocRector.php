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

namespace FastForward\DevTools\Rector;

use PHPStan\Reflection\ClassReflection;
use phpowermove\docblock\Docblock;
use phpowermove\docblock\tags\ParamTag;
use phpowermove\docblock\tags\ReturnTag;
use phpowermove\docblock\tags\ThrowsTag;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function Safe\preg_split;

final class AddMissingMethodPhpDocRector extends AbstractRector
{
    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add basic PHPDoc to methods without docblock', []);
    }

    /**
     * @return array
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    public function refactor(Node $node): Node
    {
        if (! $node instanceof ClassMethod) {
            return $node;
        }

        $docComment = $node->getDocComment();
        $docblock = $docComment instanceof Doc
            ? new Docblock($docComment->getText())
            : $this->createDocblockFromReflection($node);

        $existingParamVariables = $this->getExistingParamVariables($docblock);

        foreach ($node->params as $param) {
            $paramName = $this->getName($param->var);
            if (null === $paramName) {
                continue;
            }

            if (\in_array($paramName, $existingParamVariables, true)) {
                continue;
            }

            $paramTag = new ParamTag();
            $paramTag->setType($this->resolveTypeToString($param->type));
            $paramTag->setVariable($paramName);

            $docblock->appendTag($paramTag);
        }

        if ($this->shouldAddReturnTag($node, $docblock)) {
            $returnTag = new ReturnTag();
            $returnTag->setType($this->resolveTypeToString($node->returnType));

            $docblock->appendTag($returnTag);
        }

        $existingThrowsTypes = $this->getExistingThrowsTypes($docblock);

        foreach ($this->resolveThrows($node) as $exception) {
            if (\in_array($exception, $existingThrowsTypes, true)) {
                continue;
            }

            $throwsTag = new ThrowsTag();
            $throwsTag->setType($exception);

            $docblock->appendTag($throwsTag);
        }

        if ($docblock->isEmpty()) {
            return $node;
        }

        $node->setDocComment(new Doc($this->normalizeDocblockSpacing($docblock->toString())));

        return $node;
    }

    /**
     * @param string $docblock
     *
     * @return string
     */
    private function normalizeDocblockSpacing(string $docblock): string
    {
        $lines = preg_split('/\R/', $docblock);

        if ([] === $lines) {
            return $docblock;
        }

        $normalizedLines = [];
        $previousTagGroup = null;

        foreach ($lines as $line) {
            $currentTagGroup = $this->resolveTagGroup($line);

            if (
                null !== $currentTagGroup
                && null !== $previousTagGroup
                && $currentTagGroup !== $previousTagGroup
                && $this->shouldInsertBlankLineBetweenTagGroups($previousTagGroup, $currentTagGroup)
                && [] !== $normalizedLines
                && ' *' !== end($normalizedLines)
                && '/**' !== end($normalizedLines)
            ) {
                $normalizedLines[] = ' *';
            }

            $normalizedLines[] = $line;

            if (null !== $currentTagGroup) {
                $previousTagGroup = $currentTagGroup;
            }
        }

        return implode("\n", $normalizedLines);
    }

    /**
     * @param string $line
     *
     * @return string|null
     */
    private function resolveTagGroup(string $line): ?string
    {
        $trimmedLine = trim($line);

        if (str_starts_with($trimmedLine, '* @param ')) {
            return 'param';
        }

        if (str_starts_with($trimmedLine, '* @return ')) {
            return 'return';
        }

        if (str_starts_with($trimmedLine, '* @throws ')) {
            return 'throws';
        }

        return null;
    }

    /**
     * @param string $previousTagGroup
     * @param string $currentTagGroup
     *
     * @return bool
     */
    private function shouldInsertBlankLineBetweenTagGroups(string $previousTagGroup, string $currentTagGroup): bool
    {
        if ('param' === $previousTagGroup && 'return' === $currentTagGroup) {
            return true;
        }

        return ('param' === $previousTagGroup || 'return' === $previousTagGroup) && 'throws' === $currentTagGroup;
    }

    /**
     * @param Docblock $docblock
     *
     * @return string[]
     */
    private function getExistingParamVariables(Docblock $docblock): array
    {
        $variables = [];

        foreach ($docblock->getTags('param')->toArray() as $tag) {
            if (! $tag instanceof ParamTag) {
                continue;
            }

            $variable = $tag->getVariable();

            if ('' === $variable) {
                continue;
            }

            $variables[] = $variable;
        }

        return array_values(array_unique($variables));
    }

    /**
     * @param ClassMethod $node
     * @param Docblock $docblock
     *
     * @return bool
     */
    private function shouldAddReturnTag(ClassMethod $node, Docblock $docblock): bool
    {
        if ('__construct' === $node->name->toString()) {
            return false;
        }

        return ! $docblock->hasTag('return');
    }

    /**
     * @param Docblock $docblock
     *
     * @return string[]
     */
    private function getExistingThrowsTypes(Docblock $docblock): array
    {
        $types = [];

        foreach ($docblock->getTags('throws')->toArray() as $tag) {
            if (! $tag instanceof ThrowsTag) {
                continue;
            }

            $type = $tag->getType();

            if ('' === $type) {
                continue;
            }

            $types[] = $type;
        }

        return array_values(array_unique($types));
    }

    /**
     * @param ClassMethod $node
     *
     * @return string[]
     */
    private function resolveThrows(ClassMethod $node): array
    {
        if (null === $node->stmts) {
            return [];
        }

        $nodeFinder = new NodeFinder();

        /** @var Throw_[] $throwNodes */
        $throwNodes = $nodeFinder->findInstanceOf($node->stmts, Throw_::class);

        $exceptions = [];

        foreach ($throwNodes as $throwNode) {
            if (! $throwNode->expr instanceof New_) {
                continue;
            }

            if (! $throwNode->expr->class instanceof Name) {
                continue;
            }

            $exceptions[] = $this->resolveNameToString($throwNode->expr->class);
        }

        return array_values(array_unique($exceptions));
    }

    /**
     * @param Name $name
     *
     * @return string
     */
    private function resolveNameToString(Name $name): string
    {
        $originalName = $name->getAttribute('originalName');

        if ($originalName instanceof Name) {
            return $originalName->toString();
        }

        return $name->getLast();
    }

    /**
     * @param ClassMethod $node
     *
     * @return Docblock
     */
    private function createDocblockFromReflection(ClassMethod $node): Docblock
    {
        $scope = ScopeFetcher::fetch($node);
        $classReflection = $scope->getClassReflection();

        if (! $classReflection instanceof ClassReflection) {
            return new Docblock('/** */');
        }

        $methodName = $this->getName($node->name);

        if (null === $methodName) {
            return new Docblock('/** */');
        }

        $nativeReflection = $classReflection->getNativeReflection();

        if (! $nativeReflection->hasMethod($methodName)) {
            return new Docblock('/** */');
        }

        $reflectionMethod = $nativeReflection->getMethod($methodName);
        $reflectionDocComment = $reflectionMethod->getDocComment();

        if (! \is_string($reflectionDocComment) || '' === $reflectionDocComment) {
            return new Docblock('/** */');
        }

        return new Docblock($reflectionDocComment);
    }

    /**
     * @param string|Identifier|Name|ComplexType|null $type
     *
     * @return string
     */
    private function resolveTypeToString(string|Identifier|Name|ComplexType|null $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        if (\is_string($type)) {
            return $type;
        }

        if ($type instanceof Identifier) {
            return $type->toString();
        }

        if ($type instanceof Name) {
            $originalName = $type->getAttribute('originalName');

            if ($originalName instanceof Name) {
                return $originalName->toString();
            }

            return $type->toString();
        }

        if ($type instanceof NullableType) {
            return $this->resolveTypeToString($type->type) . '|null';
        }

        if ($type instanceof UnionType) {
            return implode('|', array_map($this->resolveTypeToString(...), $type->types));
        }

        if ($type instanceof IntersectionType) {
            return implode('&', array_map($this->resolveTypeToString(...), $type->types));
        }

        return 'mixed';
    }
}
