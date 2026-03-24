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

use PhpParser\Comment\Doc;
use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use PhpParser\Node\Stmt\Class_;

final class AddMissingClassPhpDocRector extends AbstractRector
{
    /**
     * @return RuleDefinition
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add basic PHPDoc to classes without docblock', []);
    }

    /**
     * @return array
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Node $node
     *
     * @return Node|null
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
