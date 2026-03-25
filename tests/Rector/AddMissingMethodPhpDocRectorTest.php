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
use Prophecy\PhpUnit\ProphecyTrait;

#[CoversClass(AddMissingMethodPhpDocRector::class)]
final class AddMissingMethodPhpDocRectorTest extends TestCase
{
    use ProphecyTrait;

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
    public function refactorWillAddTagsToMethodWithoutExistingDocComment(): void
    {
        $node = new ClassMethod('testMethod');
        $node->setDocComment(new Doc("/**\n */")); // Avoid ScopeFetcher
        $node->returnType = new Identifier('void');

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(ClassMethod::class, $result);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        self::assertStringContainsString('@return void', $doc->getText());
    }

    #[Test]
    public function refactorWillHandleComplexTypes(): void
    {
        $node = new ClassMethod('complexTypes');
        $node->setDocComment(new Doc("/**\n */"));
        
        $param1 = new Param(new \PhpParser\Node\Expr\Variable('nullable'));
        $param1->type = new \PhpParser\Node\NullableType(new Identifier('string'));
        
        $param2 = new Param(new \PhpParser\Node\Expr\Variable('union'));
        $param2->type = new \PhpParser\Node\UnionType([new Identifier('int'), new Identifier('float')]);

        $param3 = new Param(new \PhpParser\Node\Expr\Variable('intersection'));
        $param3->type = new \PhpParser\Node\IntersectionType([new Name('A'), new Name('B')]);

        $node->params = [$param1, $param2, $param3];
        $node->returnType = new Name('static');

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        self::assertStringContainsString('@param string|null $nullable', $doc);
        self::assertStringContainsString('@param int|float $union', $doc);
        self::assertStringContainsString('@param A&B $intersection', $doc);
        self::assertStringContainsString('@return static', $doc);
        
        // Verify spacing (blank line between param and return)
        self::assertStringContainsString("\n * @param A&B \$intersection\n *\n * @return static", $doc);
    }

    #[Test]
    public function resolveThrowsWillSkipNonNewOrNonNameExpressions(): void
    {
        $node = new ClassMethod('throwsEdgeCases');
        $node->setDocComment(new Doc("/**\n */"));
        
        // throw $e; (not a New_ node)
        $throw1 = new \PhpParser\Node\Stmt\Expression(new Throw_(new \PhpParser\Node\Expr\Variable('e')));
        
        // throw new $class(); (class is not a Name node)
        $throw2 = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new \PhpParser\Node\Expr\Variable('class'))));
        
        $node->stmts = [$throw1, $throw2];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        // No tags should be added beyond the initial ones (which are none), 
        // but normalizeDocblockSpacing might still run.
        self::assertStringNotContainsString('@throws', $doc);
    }

    #[Test]
    public function resolveThrowsWillHandleMultipleAndDuplicates(): void
    {
        $node = new ClassMethod('multipleThrows');
        $node->setDocComment(new Doc("/**\n */"));
        
        $throw1 = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new Name('RuntimeException'))));
        $throw2 = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new Name('RuntimeException'))));
        $throw3 = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new Name('Exception'))));
        
        $node->stmts = [$throw1, $throw2, $throw3];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        self::assertStringContainsString('@throws RuntimeException', $doc);
        self::assertStringContainsString('@throws Exception', $doc);
    }

    #[Test]
    public function refactorWillInsertBlankLinesBetweenTagGroups(): void
    {
        $node = new ClassMethod('spacingTest');
        $node->setDocComment(new Doc("/**\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('p'));
        $param->type = new Identifier('string');
        $node->params = [$param];
        
        $node->returnType = new Identifier('int');
        
        $throw = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new Name('Exception'))));
        $node->stmts = [$throw];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        // Should have blank lines between all different groups
        self::assertStringContainsString("@param string \$p\n *\n * @throws Exception\n *\n * @return int", $doc);
    }

    #[Test]
    public function refactorWillNotAddDuplicateTags(): void
    {
        $node = new ClassMethod('duplicates');
        $node->setDocComment(new Doc("/**\n * @param string \$p\n * @throws Exception\n * @return int\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('p'));
        $param->type = new Identifier('string');
        $node->params = [$param];
        
        $node->returnType = new Identifier('int');
        
        $throw = new \PhpParser\Node\Stmt\Expression(new Throw_(new New_(new Name('Exception'))));
        $node->stmts = [$throw];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        self::assertSame(1, substr_count($doc, '@param string $p'));
        self::assertSame(1, substr_count($doc, '@throws Exception'));
        self::assertSame(1, substr_count($doc, '@return int'));
    }

    #[Test]
    public function refactorWillNormalizeExistingSpacing(): void
    {
        $node = new ClassMethod('normalize');
        // Messy spacing
        $node->setDocComment(new Doc("/**\n * @param string \$p\n\n\n * @return int\n */"));
        
        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        // Multiple blank lines should be collapsed to one
        self::assertStringNotContainsString("\n *\n *\n *", $doc);
        self::assertStringContainsString("@param string \$p\n *\n * @return int", $doc);
    }

    #[Test]
    public function refactorWillHandleIntersectionsAndUnions(): void
    {
        $node = new ClassMethod('complexTypes');
        $node->setDocComment(new Doc("/**\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('p'));
        $param->type = new \PhpParser\Node\IntersectionType([
            new \PhpParser\Node\Name\FullyQualified('Iterator'),
            new \PhpParser\Node\Name\FullyQualified('Countable')
        ]);
        $node->params = [$param];
        
        $node->returnType = new \PhpParser\Node\UnionType([
            new Identifier('string'),
            new Identifier('int')
        ]);

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        self::assertStringContainsString("@param Iterator&Countable \$p", $doc);
        self::assertStringContainsString("@return string|int", $doc);
    }

    #[Test]
    public function refactorWillHandleMixedAndMessyTags(): void
    {
        $node = new ClassMethod('messy');
        // Unordered and messy spacing/tags
        $node->setDocComment(new Doc("/**\n * @return void\n * @param string \$a\n * @throws \Exception\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('a'));
        $param->type = new Identifier('string');
        $node->params = [$param];
        
        $node->returnType = new Identifier('void');
        
        $throw = new \PhpParser\Node\Stmt\Expression(new \PhpParser\Node\Expr\Throw_(new \PhpParser\Node\Expr\New_(new \PhpParser\Node\Name\FullyQualified('Exception'))));
        $node->stmts = [$throw];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        // Should reorder and normalize spacing
        // Order is param, throws, return. Blank lines between DIFFERENT groups.
        self::assertStringContainsString("@param string \$a\n *\n * @throws \Exception\n *\n * @return void", $doc);
    }

    #[Test]
    public function refactorWillHandleIntersectionsAndUnionsWithFullyQualifiedNames(): void
    {
        $node = new ClassMethod('fqnTypes');
        $node->setDocComment(new Doc("/**\n */"));
        
        $param = new Param(new \PhpParser\Node\Expr\Variable('p'));
        $param->type = new \PhpParser\Node\IntersectionType([
            new \PhpParser\Node\Name\FullyQualified('ArrayAccess'),
            new \PhpParser\Node\Name\FullyQualified('Countable')
        ]);
        $node->params = [$param];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()->getText();

        self::assertStringContainsString("@param ArrayAccess&Countable \$p", $doc);
    }
}
