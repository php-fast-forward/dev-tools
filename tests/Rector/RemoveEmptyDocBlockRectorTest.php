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

namespace FastForward\DevTools\Tests\Rector;

use ReflectionClass;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeAnalyzer\CallAnalyzer;
use Rector\Rector\AbstractRector;
use PhpParser\Node\Expr\Variable;
use FastForward\DevTools\Rector\RemoveEmptyDocBlockRector;
use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RemoveEmptyDocBlockRector::class)]
final class RemoveEmptyDocBlockRectorTest extends TestCase
{
    private RemoveEmptyDocBlockRector $rector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->rector = new RemoveEmptyDocBlockRector();

        $nodeNameResolver = (new ReflectionClass(
            NodeNameResolver::class
        ))->newInstanceWithoutConstructor();

        $resolverReflection = new ReflectionClass(NodeNameResolver::class);

        if ($resolverReflection->hasProperty('nodeNameResolvers')) {
            $prop = $resolverReflection->getProperty('nodeNameResolvers');
            $prop->setValue($nodeNameResolver, []);
        }

        if ($resolverReflection->hasProperty('nodeNameResolversByClass')) {
            $prop = $resolverReflection->getProperty('nodeNameResolversByClass');
            $prop->setValue($nodeNameResolver, []);
        }

        if ($resolverReflection->hasProperty('callAnalyzer')) {
            $prop = $resolverReflection->getProperty('callAnalyzer');
            $prop->setValue(
                $nodeNameResolver,
                (new ReflectionClass(CallAnalyzer::class))->newInstanceWithoutConstructor()
            );
        }

        $reflection = new ReflectionClass(AbstractRector::class);
        $property = $reflection->getProperty('nodeNameResolver');
        $property->setValue($this->rector, $nodeNameResolver);
    }

    /**
     * @return void
     */
    #[Test]
    public function getRuleDefinitionWillReturnConfiguredDefinition(): void
    {
        self::assertSame(
            'Remove empty docblocks from classes and methods',
            $this->rector->getRuleDefinition()->getDescription()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function getNodeTypesWillReturnClassAndClassMethodNode(): void
    {
        self::assertSame([Class_::class, ClassMethod::class], $this->rector->getNodeTypes());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullIfNotClassOrClassMethodNode(): void
    {
        $node = new Function_('test');

        self::assertNull($this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullIfNodeHasNoDocComment(): void
    {
        $node = new Class_('TestClass');

        self::assertNull($this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullIfDocCommentIsNotEmpty(): void
    {
        $node = new Class_('TestClass');
        $node->setDocComment(new Doc("/**\n * Test class\n */"));

        self::assertNull($this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillRemoveEmptyDocComment(): void
    {
        $node = new ClassMethod('testMethod');
        $doc = new Doc("/**\n *\n */");
        $comment = new Comment('// test comment');

        $node->setDocComment($doc);
        $node->setAttribute('comments', [$comment, $doc]);

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(ClassMethod::class, $result);
        self::assertNull($result->getDocComment());
        self::assertSame([$comment], $result->getAttribute('comments'));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillRemoveSingleLineEmptyDocComment(): void
    {
        $node = new Class_('TestClass');
        $node->setDocComment(new Doc('/** */'));

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(Class_::class, $result);
        self::assertNull($result->getDocComment());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillRemoveEmptyDocCommentWithSpaces(): void
    {
        $node = new ClassMethod('testMethod');
        $node->setDocComment(new Doc("/**  \n  *  \n  */"));

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(ClassMethod::class, $result);
        self::assertNull($result->getDocComment());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullForNonSupportedNodes(): void
    {
        $node = new Variable('var');
        self::assertNull($this->rector->refactor($node));
    }
}
