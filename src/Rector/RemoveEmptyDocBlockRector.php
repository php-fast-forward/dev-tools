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

final class RemoveEmptyDocBlockRector extends AbstractRector
{
    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Remove empty docblocks from classes and methods', []);
    }

    /**
     * @return array
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, ClassMethod::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
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
     * @param string $docBlock
     *
     * @return bool
     */
    private function isEmptyDocBlock(string $docBlock): bool
    {
        $docblock = new Docblock($docBlock);

        if (! $docblock->isEmpty()) {
            return false;
        }

        $lines = preg_split('/\R/', $docBlock);

        if (! \is_array($lines)) {
            return false;
        }

        foreach ($lines as $line) {
            $normalizedLine = trim((string) $line);
            if ('/**' === $normalizedLine) {
                continue;
            }

            if ('*/' === $normalizedLine) {
                continue;
            }

            if ('*' === $normalizedLine) {
                continue;
            }

            $normalizedLine = preg_replace('#^/\*\*\s*#', '', $normalizedLine);
            $normalizedLine = preg_replace('#^\*\s?#', '', (string) $normalizedLine);
            $normalizedLine = preg_replace('#\s*\*/$#', '', (string) $normalizedLine);
            $normalizedLine = trim((string) $normalizedLine);

            if ('' !== $normalizedLine) {
                return false;
            }
        }

        return true;
    }
}
