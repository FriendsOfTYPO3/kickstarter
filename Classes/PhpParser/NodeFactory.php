<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\PhpParser;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use PhpParser\BuilderFactory;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\DeclareDeclare;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\Use_;

readonly class NodeFactory
{
    private BuilderFactory $factory;

    public function __construct()
    {
        $this->factory = new BuilderFactory();
    }

    /**
     * Creates a strict_type declaration node which will be rendered as:
     *
     * declare(strict_types=1);
     */
    public function createDeclareStrictTypes(): Declare_
    {
        return new Declare_([
            new DeclareDeclare(
                'strict_types',
                new LNumber(1)
            ),
        ]);
    }

    /**
     * Creates a use import node which will be rendered as:
     *
     * use TYPO3\CMS\Core\Utility\GeneralUtility;
     */
    public function createUseImport(string $useImport): Use_
    {
        return $this->factory
            ->use($useImport)
            ->getNode();
    }

    /**
     * Creates a namespace node which will be rendered as:
     *
     * namespace MyVendor\MyExt\Domain\Model;
     *
     * Don't forget to add the "use" and "class" nodes to this resulting node: $namespaceNode->stmts[]
     */
    public function createNamespace(string $namespace, ExtensionInformation $extensionInformation): Namespace_
    {
        $docComment = <<<'EOT'
/*
 * This file is part of the package {PACKAGE_NAME}.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */
EOT;

        return $this->factory
            ->namespace($namespace)
            ->setDocComment(
                str_replace('{PACKAGE_NAME}', $extensionInformation->getComposerPackageName(), $docComment)
            )
            ->getNode();
    }

    /**
     * Creates a class node which will be rendered as:
     *
     * class BlogController {}
     *
     * Don't forget to add the "classConst", "trait", "property" and "method" nodes to this resulting node:
     * $classNode->stmts[]
     *
     * It is YOUR job to also register the use imports with ::createUseImport()
     */
    public function createClass(string $className): Class_
    {
        return $this->factory
            ->class($className)
            ->getNode();
    }

    /**
     * Creates a use trait node which will be rendered as:
     *
     * use GetMapHelperTrait;
     *
     * It is YOUR job to also register its use import with ::createUseImport()
     */
    public function createUseTrait(string $traitName): TraitUse
    {
        // In ->getNode() only "Node" was registered as a return value. Adding TraitUse here, too
        /** @var TraitUse $node */
        $node = $this->factory
            ->useTrait($traitName)
            ->getNode();

        return $node;
    }

    /**
     * Creates a class const node which will be rendered as:
     *
     * public TEMPLATE = 'EXT:my_ext/Resources/Private/Templates/Page.html';
     */
    public function createClassConst(string $classConstName, mixed $value): ClassConst
    {
        return $this->factory
            ->classConst($classConstName, $value)
            ->getNode();
    }

    /**
     * Creates a class property node which will be rendered as:
     *
     * protected string $address = '';
     */
    public function createProperty(string $propertyName): Property
    {
        return $this->factory
            ->property($propertyName)
            ->makeProtected()
            ->getNode();
    }

    /**
     * Creates a class method node which will be rendered as:
     *
     * public function getFillColor(): string
     * {
     *     return $this->fillColor;
     * }
     */
    public function createMethod(string $methodName): ClassMethod
    {
        return $this->factory
            ->method($methodName)
            ->makePublic()
            ->getNode();
    }

    /*
     * BELOW YOU WILL FIND HELPER METHODS FOR SPECIFIC TASKS
     */

    /**
     * Creates an extbase controller class node which will be rendered as:
     *
     * class BlogController extends ActionController {}
     *
     * Don't forget to add the "classConst", "trait", "property" and "method" nodes to this resulting node:
     * $classNode->stmts[]
     *
     * It is YOUR job to also register the use imports with ::createUseImport()
     */
    public function createExtbaseControllerClass(string $className): Class_
    {
        return $this->factory
            ->class($className)
            ->extend('ActionController')
            ->getNode();
    }

    public function createMiddlewareClass(string $className): Class_
    {
        return $this->factory
            ->class($className)
            ->implement('MiddlewareInterface')
            ->getNode();
    }

    /**
     * Creates a non extbase based controller action method node which will be rendered as:
     *
     * public function showAction(): ResponseInterface
     * {
     *     return new HtmlResponse('Hello World!');
     * }
     */
    public function createControllerActionMethod(string $methodName): ClassMethod
    {
        return $this->factory
            ->method($methodName)
            ->makePublic()
            ->setReturnType('ResponseInterface')
            ->addStmts([
                new Return_(
                    new New_(
                        new Name('HtmlResponse'),
                        [
                            new Arg(new String_('Hello World!')),
                        ]
                    )
                ),
            ])
            ->getNode();
    }

    /**
     * Creates an extbase based controller action method node which will be rendered as:
     *
     * public function showAction(): ResponseInterface
     * {
     *     return $this->htmlResponse();
     * }
     */
    public function createExtbaseControllerActionMethod(string $methodName): ClassMethod
    {
        return $this->factory
            ->method($methodName)
            ->makePublic()
            ->setReturnType('ResponseInterface')
            ->addStmts([
                new Return_(
                    new MethodCall(
                        new Variable('this'),
                        'htmlResponse'
                    )
                ),
            ])
            ->getNode();
    }

    public function createMiddlewareProcessMethod(): ClassMethod
    {
        return $this->factory
            ->method('process')
            ->makePublic()
            ->addParam($this->factory->param('request')->setType('ServerRequestInterface'))
            ->addParam($this->factory->param('handler')->setType('RequestHandlerInterface'))
            ->setReturnType('ResponseInterface')
            ->addStmts([
                new Return_(
                    new MethodCall(
                        new Variable('handler'),
                        'handle',
                        [new Arg(new Variable('request'))]
                    )
                ),
            ])
            ->getNode();
    }

    /**
     * Creates a PhpParser expression node representing a literal value.
     *
     * Supports scalars, booleans, null, and arrays.
     *
     * Example usages:
     *
     * // Scalar
     * $this->createValue('hello'); // 'hello'
     * $this->createValue(42);      // 42
     *
     * // Boolean
     * $this->createValue(true);    // true
     *
     * // Null
     * $this->createValue(null);    // null
     *
     * // Array
     * $this->createValue(['foo', 'bar']); // ['foo', 'bar']
     *
     * // Associative Array
     * $this->createValue(['key' => 'value']); // ['key' => 'value']
     */
    public function createValue(mixed $value): Expr
    {
        if (is_int($value)) {
            return new LNumber($value);
        }
        if (is_float($value)) {
            return new DNumber($value);
        }
        if (is_string($value)) {
            return new String_($value);
        }
        if (is_bool($value)) {
            return new ConstFetch(
                new Name($value ? 'true' : 'false')
            );
        }
        if (is_null($value)) {
            return new ConstFetch(new Name('null'));
        }
        if (is_array($value)) {
            $items = [];
            foreach ($value as $k => $v) {
                $items[] = new ArrayItem(
                    $this->createValue($v),
                    is_int($k) ? null : new String_($k)
                );
            }
            return new Array_($items, ['kind' => Array_::KIND_SHORT]);
        }

        throw new \InvalidArgumentException('Unsupported default value type: ' . gettype($value), 2697872207);
    }
}
