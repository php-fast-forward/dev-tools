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

use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
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
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\UnionType;
use PhpParser\NodeFinder;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function Safe\preg_split;

/**
 * Executes AST inspections parsing missing documentation on methods automatically.
 * It MUST append `@param`, `@return`, and `@throws` tags where deduced accurately.
 * The logic SHALL NOT override existing documentation.
 */
final class AddMissingMethodPhpDocRector extends AbstractRector
{
    /**
     * Delivers the formal rule description configured within the Rector ecosystem.
     *
     * The method MUST accurately describe its functional changes logically.
     *
     * @return RuleDefinition explains the rule's active behavior context
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add basic PHPDoc to methods without docblock', [
            new CodeSample('public function foo() {}', "/**\n * \n */\npublic function foo() {}"),
        ]);
    }

    /**
     * Designates the primary Abstract Syntax Tree (AST) node structures intercepted.
     *
     * The method MUST register solely `ClassMethod` class references to guarantee precision.
     *
     * @return array<int, class-string<Node>> the structural bindings applicable for this modification
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * Computes necessary PHPDoc metadata for a given class method selectively.
     *
     * The method MUST identify the missing `@param`, `@return`, and `@throws` tags algorithmically.
     * It SHALL preserve pre-existing valid tags cleanly. If no augmentation is achieved, it returns the node unaltered.
     *
     * @param Node $node the target method representation parsed synchronously
     *
     * @return Node the refined active syntax instance inclusive of generated documentation
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
            $normalizedException = ltrim($exception, '\\');
            if (\in_array($normalizedException, $existingThrowsTypes, true)) {
                continue;
            }

            $throwsTag = new ThrowsTag();
            $throwsTag->setType($exception);

            $docblock->appendTag($throwsTag);
            $existingThrowsTypes[] = $normalizedException;
        }

        if ($docblock->isEmpty()) {
            return $node;
        }

        $node->setDocComment(new Doc($this->normalizeDocblockSpacing($docblock->toString())));

        return $node;
    }

    /**
     * Formats the newly synthesized document block optimally, balancing whitespaces and gaps.
     *
     * This method MUST ensure visual spacing between separate tag families (e.g., between param and return).
     * It SHALL preserve the structural integrity of the PHPDoc format effectively.
     *
     * @param string $docblock the unsanitized raw string equivalent of the document block
     *
     * @return string the formatted textual content accurately respecting conventions
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
     * Attempts to resolve the functional category inherent to a documentation tag.
     *
     * The method MUST parse the string descriptor reliably, extracting the tag intention logically.
     *
     * @param string $line the single document property statement being reviewed
     *
     * @return string|null the functional label or null if unbound correctly
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
     * Concludes if architectural clarity requires an explicit blank interval.
     *
     * The method MUST mandate proper line spacing between `@param`, `@return`, and `@throws` groups.
     *
     * @param string $previousTagGroup the prior tag context encountered
     * @param string $currentTagGroup the newly active tag context currently processing
     *
     * @return bool true if an empty marker requires insertion natively
     */
    private function shouldInsertBlankLineBetweenTagGroups(string $previousTagGroup, string $currentTagGroup): bool
    {
        return $previousTagGroup !== $currentTagGroup;
    }

    /**
     * Collates variables already declared adequately within the existing documentation base.
     *
     * This method MUST retrieve predefined `@param` configurations logically, avoiding collisions.
     *
     * @param Docblock $docblock the active parsed commentary structure instance
     *
     * @return string[] uniquely filtered established parameters
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
     * Calculates whether a `@return` tag is fundamentally valid for the given context.
     *
     * The method SHALL exclude magic implementations such as `__construct` deliberately.
     *
     * @param ClassMethod $node the specific operation structure verified securely
     * @param Docblock $docblock the connected documentation references
     *
     * @return bool indicates validation explicitly approving return blocks selectively
     */
    private function shouldAddReturnTag(ClassMethod $node, Docblock $docblock): bool
    {
        if ('__construct' === $node->name->toString()) {
            return false;
        }

        return ! $docblock->hasTag('return');
    }

    /**
     * Assembles all established exceptions logged intentionally within the existing tag array.
     *
     * The method MUST enumerate declared `@throws` statements efficiently.
     *
     * @param Docblock $docblock the functional parser tree model internally loaded
     *
     * @return string[] discovered types of configured operational errors generically
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

            $types[] = ltrim($type, '\\');
        }

        return array_values(array_unique($types));
    }

    /**
     * Parses the architectural scope of an intercepted method to infer exceptional operations natively.
     *
     * This method MUST accurately deduce exception creations traversing internal components recursively.
     * It SHALL strictly return precise, unique internal naming identifiers safely.
     *
     * @param ClassMethod $node the active evaluated root target element dynamically instantiated
     *
     * @return string[] expected failure objects effectively defined within its contextual boundary
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
            $throwExpr = $throwNode->expr;

            if (! $throwExpr instanceof New_) {
                continue;
            }

            if (! $throwExpr->class instanceof Name) {
                continue;
            }

            $exceptions[] = $this->resolveNameToString($throwExpr->class);
        }

        return array_values(array_unique($exceptions));
    }

    /**
     * Expands Name syntax objects into human-readable string descriptors universally.
     *
     * The method MUST handle aliases seamlessly or fallback to base names dependably.
     *
     * @param Name $name the structured reference to parse accurately
     *
     * @return string the computed class identifier successfully reconstructed
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
     * Evaluates PHPStan reflection metadata securely deriving original PHPDoc components.
     *
     * The method SHOULD establish scope accurately and fetch reliable documentation defaults safely.
     *
     * @param ClassMethod $node the associated target structure explicitly handled internally
     *
     * @return Docblock the built virtualized docblock reference precisely retrieved natively
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
     * Translates complicated type primitives cleanly back into uniform string declarations consistently.
     *
     * The method MUST parse complex combinations including Intersections, Unions natively and securely.
     *
     * @param string|Identifier|Name|ComplexType|null $type the original metadata instance safely captured
     *
     * @return string the final interpreted designation string explicitly represented safely
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
