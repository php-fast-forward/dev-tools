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

namespace FastForward\DevTools\Rector;

use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
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
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

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

        if ($docComment instanceof Doc) {
            return $node;
        }

        $newTags = [];
        foreach ($node->params as $param) {
            $paramName = $this->getName($param->var);

            if (null === $paramName) {
                continue;
            }

            $newTags[] = \sprintf(' * @param %s $%s', $this->resolveTypeToString($param->type), $paramName);
        }

        if ('__construct' !== $node->name->toString()) {
            if ([] !== $newTags) {
                $newTags[] = ' *';
            }

            $newTags[] = \sprintf(' * @return %s', $this->resolveTypeToString($node->returnType));
        }

        foreach ($this->resolveThrows($node) as $exception) {
            if ([] !== $newTags) {
                $newTags[] = ' *';
            }

            $newTags[] = \sprintf(' * @throws %s', $exception);
        }

        if ([] === $newTags) {
            return $node;
        }

        $docBlock = "/**\n" . implode("\n", $newTags) . "\n */";

        $node->setDocComment(new Doc($docBlock));

        return $node;
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
