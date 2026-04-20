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

namespace FastForward\DevTools\Tests\Rector;

use ReflectionClass;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeAnalyzer\CallAnalyzer;
use Rector\Rector\AbstractRector;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PhpParser\Node\IntersectionType;
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

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->rector = new AddMissingMethodPhpDocRector();

        $nodeNameResolver = (new ReflectionClass(NodeNameResolver::class))->newInstanceWithoutConstructor();

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
            'Add basic PHPDoc to methods without docblock',
            $this->rector->getRuleDefinition()
                ->getDescription()
        );
    }

    /**
     * @return void
     */
    #[Test]
    public function getNodeTypesWillReturnClassMethodNode(): void
    {
        self::assertSame([ClassMethod::class], $this->rector->getNodeTypes());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNodeIfNotClassMethod(): void
    {
        $node = new Class_('test');

        self::assertSame($node, $this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocblockIfMissing(): void
    {
        $node = new ClassMethod('testMethod');
        $param = new Param(new Variable('testVar'));
        $param->type = new Identifier('string');

        $node->params = [$param];
        $node->returnType = new Identifier('bool');

        $throw = new Expression(new Throw_(new New_(new FullyQualified('RuntimeException'))));
        $node->stmts = [$throw];

        $result = $this->rector->refactor($node);
        self::assertInstanceOf(ClassMethod::class, $result);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        $text = $doc->getText();
        self::assertStringContainsString('@param string $testVar', $text);
        self::assertStringContainsString('@return bool', $text);
        self::assertStringContainsString('@throws RuntimeException', $text);
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillNotChangeIfDocblockExists(): void
    {
        $node = new ClassMethod('testMethod');
        $node->setDocComment(new Doc("/**\n * Já existe\n */"));
        $node->returnType = new Identifier('void');

        $result = $this->rector->refactor($node);
        self::assertSame($node, $result);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        self::assertStringContainsString('Já existe', $doc->getText());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocblockWithComplexTypes(): void
    {
        $node = new ClassMethod('complexTypes');
        $param1 = new Param(new Variable('nullable'));
        $param1->type = new NullableType(new Identifier('string'));

        $param2 = new Param(new Variable('union'));
        $param2->type = new UnionType([new Identifier('int'), new Identifier('float')]);

        $param3 = new Param(new Variable('intersection'));
        $param3->type = new IntersectionType([new Name('A'), new Name('B')]);

        $node->params = [$param1, $param2, $param3];
        $node->returnType = new Name('static');

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()
            ->getText();
        self::assertStringContainsString('@param string|null $nullable', $doc);
        self::assertStringContainsString('@param int|float $union', $doc);
        self::assertStringContainsString('@param A&B $intersection', $doc);
        self::assertStringContainsString('@return static', $doc);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveThrowsWillSkipNonNewOrNonNameExpressions(): void
    {
        $node = new ClassMethod('throwsEdgeCases');
        // throw $e; (not a New_ node)
        $throw1 = new Expression(new Throw_(new Variable('e')));
        // throw new $class(); (class is not a Name node)
        $throw2 = new Expression(new Throw_(new New_(new Variable('class'))));
        $node->stmts = [$throw1, $throw2];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        $text = $doc->getText();
        self::assertStringContainsString('@return mixed', $text);
    }

    /**
     * @return void
     */
    #[Test]
    public function resolveThrowsWillHandleMultipleAndDuplicates(): void
    {
        $node = new ClassMethod('multipleThrows');
        $throw1 = new Expression(new Throw_(new New_(new Name('RuntimeException'))));
        $throw2 = new Expression(new Throw_(new New_(new Name('RuntimeException'))));
        $throw3 = new Expression(new Throw_(new New_(new Name('Exception'))));
        $node->stmts = [$throw1, $throw2, $throw3];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()
            ->getText();
        self::assertStringContainsString('@throws RuntimeException', $doc);
        self::assertStringContainsString('@throws Exception', $doc);
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocblockWithIntersectionsAndUnions(): void
    {
        $node = new ClassMethod('complexTypes');
        $param = new Param(new Variable('p'));
        $param->type = new IntersectionType([new FullyQualified('Iterator'), new FullyQualified('Countable')]);

        $node->params = [$param];
        $node->returnType = new UnionType([new Identifier('string'), new Identifier('int')]);

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()
            ->getText();
        self::assertStringContainsString('@param Iterator&Countable $p', $doc);
        self::assertStringContainsString('@return string|int', $doc);
    }

    /**
     * @return void
     */
    // Não há mais reordenação ou normalização de tags, então este teste foi removido.

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocblockWithFullyQualifiedIntersections(): void
    {
        $node = new ClassMethod('fqnTypes');
        $param = new Param(new Variable('p'));
        $param->type = new IntersectionType([new FullyQualified('ArrayAccess'), new FullyQualified('Countable')]);

        $node->params = [$param];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment()
            ->getText();
        self::assertStringContainsString('@param ArrayAccess&Countable $p', $doc);
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillSkipReturnTagForConstructorsAndRespectOriginalTypeNames(): void
    {
        $node = new ClassMethod('__construct');
        $param = new Param(new Variable('dependency'));
        $param->type = new FullyQualified('Acme\\Contract');
        $param->type->setAttribute('originalName', new Name('ContractAlias'));

        $node->params = [$param];
        $node->stmts = [new Expression(new Throw_(new New_(new FullyQualified('DomainException'))))];

        $result = $this->rector->refactor($node);
        $doc = $result->getDocComment();
        self::assertNotNull($doc);
        $text = $doc->getText();
        self::assertStringContainsString('@param ContractAlias $dependency', $text);
        self::assertStringContainsString('@throws DomainException', $text);
        self::assertStringNotContainsString('@return', $text);
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnOriginalNodeWhenNoTagsCanBeGenerated(): void
    {
        $node = new ClassMethod('__construct');
        $node->params = [new Param(new ArrayDimFetch(new Variable('items')))];
        $node->stmts = null;

        self::assertSame($node, $this->rector->refactor($node));
        self::assertNull($node->getDocComment());
    }
}
