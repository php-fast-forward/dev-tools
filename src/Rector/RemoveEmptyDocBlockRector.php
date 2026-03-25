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

use phpowermove\docblock\Docblock;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function Safe\preg_split;
use function Safe\preg_replace;

/**
 * Implements automation targeting the removal of purposeless empty DocBlock structures natively.
 * It MUST intercept specific nodes exclusively and SHALL prune invalid redundant properties transparently.
 */
final class RemoveEmptyDocBlockRector extends AbstractRector
{
    /**
     * Resolves the defined documentation object detailing expected behavior parameters intrinsically.
     *
     * The method MUST clarify accurately to external systems the primary objective successfully.
     *
     * @return RuleDefinition the instantiated declaration reference properly bounded natively
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove empty docblocks from classes and methods', [
            new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(
                "/**\n *\n */\nclass SomeClass {}",
                "class SomeClass {}"
            )
        ]);
    }

    /**
     * Exposes intercepted root AST targets consistently during analytical sweeps functionally.
     *
     * The method MUST enforce inspections primarily on class frames and class components cleanly.
     *
     * @return array<int, class-string<Node>> bound runtime types reliably tracked correctly
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, ClassMethod::class];
    }

    /**
     * Strips empty document definitions structurally from the designated AST dynamically parsed.
     *
     * The method MUST systematically evaluate content verifying an absolute absence accurately.
     * If validated, it SHALL destroy the related virtual node properties carefully.
     *
     * @param Node $node the dynamic input tree chunk inherently processed strictly
     *
     * @return Node|null the streamlined object successfully truncated or null unhandled
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof Class_ && ! $node instanceof ClassMethod) {
            return null;
        }

        $docComment = $node->getDocComment();

        if (! $docComment instanceof Doc) {
            return null;
        }

        if (! $this->isEmptyDocBlock($docComment->getText())) {
            return null;
        }

        $remainingComments = [];

        foreach ($node->getComments() as $comment) {
            if ($comment === $docComment) {
                continue;
            }

            $remainingComments[] = $comment;
        }

        $node->setDocComment(new Doc(''));
        $node->setAttribute('comments', $remainingComments);
        $node->setAttribute('docComment', null);
        $node->setAttribute('php_doc_info', null);

        return $node;
    }

    /**
     * Ascertains visually and technically if a provided block comprises an absolute empty placeholder structure safely.
     *
     * The method MUST strip control characters accurately isolating legitimate characters completely.
     *
     * @param string $docBlock the textual contents actively extracted continuously dynamically natively
     *
     * @return bool success configuration inherently signaling absolute absence accurately effectively strictly
     */
    private function isEmptyDocBlock(string $docBlock): bool
    {
        $lines = preg_split('/\R/', $docBlock);

        if (! \is_array($lines)) {
            return false;
        }

        foreach ($lines as $line) {
            $normalizedLine = trim((string) $line);
            if ('/**' === $normalizedLine || '*/' === $normalizedLine || '*' === $normalizedLine) {
                continue;
            }

            $normalizedLine = preg_replace('#^/\*\*\s*#', '', $normalizedLine);
            $normalizedLine = preg_replace('#\s*\*/$#', '', (string) $normalizedLine);
            $normalizedLine = preg_replace('#^\*\s?#', '', (string) $normalizedLine);
            $normalizedLine = trim((string) $normalizedLine);

            if ('' !== $normalizedLine) {
                return false;
            }
        }

        return true;
    }
}
