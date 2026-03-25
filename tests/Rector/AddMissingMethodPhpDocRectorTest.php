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

use FastForward\DevTools\Rector\AddMissingMethodPhpDocRector;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\Throw_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddMissingMethodPhpDocRector::class)]
final class AddMissingMethodPhpDocRectorTest extends TestCase
{
    private AddMissingMethodPhpDocRector $rector;

    protected function setUp(): void
    {
        $this->rector = new AddMissingMethodPhpDocRector();

        $nodeNameResolver = (new \ReflectionClass(\Rector\NodeNameResolver\NodeNameResolver::class))->newInstanceWithoutConstructor();
        
        $resolverReflection = new \ReflectionClass(\Rector\NodeNameResolver\NodeNameResolver::class);
        
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
            $prop->setValue($nodeNameResolver, (new \ReflectionClass(\Rector\NodeAnalyzer\CallAnalyzer::class))->newInstanceWithoutConstructor());
        }

        $reflection = new \ReflectionClass(\Rector\Rector\AbstractRector::class);
        $property = $reflection->getProperty('nodeNameResolver');
        $property->setValue($this->rector, $nodeNameResolver);
    }

    #[Test]
    public function getRuleDefinitionWillReturnConfiguredDefinition(): void
    {
        self::assertSame('Add basic PHPDoc to methods without docblock', $this->rector->getRuleDefinition()->getDescription());
    }

    #[Test]
    public function getNodeTypesWillReturnClassMethodNode(): void
    {
        self::assertSame([ClassMethod::class], $this->rector->getNodeTypes());
    }
    
    #[Test]
    public function refactorWillReturnNodeIfNotClassMethod(): void
    {
        $node = new Class_('test');
        
        self::assertSame($node, $this->rector->refactor($node));
    }

    #[Test]
    public function refactorWillAddTagsToMethodWithMinimumDocComment(): void
    {
        $node = new ClassMethod('testMethod');
        $node->setDocComment(new Doc("/**\n * Description\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('testVar'));
        $param->type = new Identifier('string');
        $node->params = [$param];
        
        $node->returnType = new Identifier('bool');
        
        $throw = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new FullyQualified('RuntimeException'))));
        $node->stmts = [$throw];

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(ClassMethod::class, $result);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        self::assertStringContainsString('@param string $testVar', $doc->getText());
        self::assertStringContainsString('@return bool', $doc->getText());
        self::assertStringContainsString('@throws RuntimeException', $doc->getText());
    }

    #[Test]
    public function refactorWillNotAddReturnTagToConstruct(): void
    {
        $node = new ClassMethod('__construct');
        $node->setDocComment(new Doc("/**\n * Description\n */"));
        
        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment();
        
        self::assertNotNull($doc);
        self::assertStringNotContainsString('@return', $doc->getText());
    }
}
