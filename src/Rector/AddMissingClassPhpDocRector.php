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
use PhpParser\Comment\Doc;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use PhpParser\Node\Stmt\Class_;

/**
 * Provides automated refactoring to prepend basic PHPDoc comments on classes missing them.
 * This rule MUST adhere to AST standards and SHALL traverse `Class_` nodes exclusively.
 */
final class AddMissingClassPhpDocRector extends AbstractRector
{
    /**
     * Resolves the definition describing this rule for documentation generation.
     *
     * The method MUST return a properly instantiated RuleDefinition stating its purpose.
     *
     * @return RuleDefinition the description entity for the given Rector rule
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add basic PHPDoc to classes without docblock', [
            new CodeSample('class SomeClass {}', "/**\n * SomeClass\n */\nclass SomeClass {}"),
        ]);
    }

    /**
     * Declares the types of Abstract Syntax Tree nodes that trigger this refactoring run.
     *
     * The method MUST identify `Class_` nodes reliably. It SHALL define the interception target.
     *
     * @return array<int, class-string<Node>> an array containing registered node class references
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * Triggers the modification process against a matched AST node.
     *
     * The method MUST verify the absence of an existing PHPDoc header accurately.
     * It SHOULD append a basic boilerplate PHPDoc comment if applicable.
     * If the node is unchanged, it SHALL return null.
     *
     * @param Node $node the current active syntax instance parsed by the framework
     *
     * @return Node|null the modified active syntax state, or null if untouched
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_) {
            return null;
        }

        if ($node->getDocComment() instanceof Doc) {
            return null;
        }

        $className = $this->getName($node->name) ?? 'Class';
        $namespace = $node->namespacedName?->slice(0, -1)
            ->toString() ?? '';

        $lines = ['/**'];
        $lines[] = \sprintf(' * %s', $className);

        if ('' !== $namespace) {
            $lines[] = ' *';
            $lines[] = \sprintf(' * @package %s', $namespace);
        }

        $lines[] = ' */';

        $node->setDocComment(new Doc(implode("\n", $lines)));

        return $node;
    }
}
