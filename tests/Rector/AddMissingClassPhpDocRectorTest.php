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
use FastForward\DevTools\Rector\AddMissingClassPhpDocRector;
use PhpParser\Comment\Doc;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddMissingClassPhpDocRector::class)]
final class AddMissingClassPhpDocRectorTest extends TestCase
{
    private AddMissingClassPhpDocRector $rector;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->rector = new AddMissingClassPhpDocRector();

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
        $definition = $this->rector->getRuleDefinition();

        self::assertSame('Add basic PHPDoc to classes without docblock', $definition->getDescription());
    }

    /**
     * @return void
     */
    #[Test]
    public function getNodeTypesWillReturnClassNode(): void
    {
        self::assertSame([Class_::class], $this->rector->getNodeTypes());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullIfNotClassNode(): void
    {
        $node = new Function_('test');

        self::assertNull($this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillReturnNullIfNodeHasDocComment(): void
    {
        $node = new Class_('TestClass');
        $node->setDocComment(new Doc('/** @var string */'));

        self::assertNull($this->rector->refactor($node));
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocCommentToClassWithoutNamespace(): void
    {
        $node = new Class_('TestClass');
        $node->name = new Identifier('TestClass');
        $node->namespacedName = null;

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(Class_::class, $result);
        self::assertNotNull($result->getDocComment());
        self::assertStringContainsString(' * TestClass', $result->getDocComment()->getText());
        self::assertStringNotContainsString('@package', $result->getDocComment()->getText());
    }

    /**
     * @return void
     */
    #[Test]
    public function refactorWillAddDocCommentToClassWithNamespace(): void
    {
        $node = new Class_('TestClass');
        $node->name = new Identifier('TestClass');
        $node->namespacedName = new Name('App\\TestClass');

        $result = $this->rector->refactor($node);

        self::assertInstanceOf(Class_::class, $result);
        self::assertNotNull($result->getDocComment());
        self::assertStringContainsString(' * TestClass', $result->getDocComment()->getText());
        self::assertStringContainsString(' * @package App', $result->getDocComment()->getText());
    }
}
