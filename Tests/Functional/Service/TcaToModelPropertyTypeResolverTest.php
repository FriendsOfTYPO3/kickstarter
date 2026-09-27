<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Service;

use FriendsOfTYPO3\Kickstarter\Enums\ModelPropertyType;
use FriendsOfTYPO3\Kickstarter\Service\TcaToModelPropertyTypeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TcaToModelPropertyTypeResolverTest extends FunctionalTestCase
{
    private TcaToModelPropertyTypeResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaToModelPropertyTypeResolver();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: list<string>, 2: string}>
     */
    public static function tcaConfigToTypesDataProvider(): array
    {
        return [
            'check field' => [
                ['type' => 'check'],
                ['bool', 'int'],
                'bool',
            ],
            'number integer' => [
                ['type' => 'number'],
                ['int', 'string'],
                'int',
            ],
            'number decimal' => [
                ['type' => 'number', 'format' => 'decimal'],
                ['float', 'string'],
                'float',
            ],
            'datetime' => [
                ['type' => 'datetime'],
                [\DateTime::class, 'int', 'string'],
                \DateTime::class,
            ],
            'input' => [
                ['type' => 'input'],
                ['string'],
                'string',
            ],
            'text' => [
                ['type' => 'text'],
                ['string'],
                'string',
            ],
            'email' => [
                ['type' => 'email'],
                ['string'],
                'string',
            ],
            'password' => [
                ['type' => 'password'],
                ['string'],
                'string',
            ],
            'color' => [
                ['type' => 'color'],
                ['string'],
                'string',
            ],
            'link' => [
                ['type' => 'link'],
                ['string'],
                'string',
            ],
            'json' => [
                ['type' => 'json'],
                ['array', 'string'],
                'array',
            ],
            'file single' => [
                ['type' => 'file', 'maxitems' => 1],
                ['object', 'string'],
                'object',
            ],
            'file multiple' => [
                ['type' => 'file', 'maxitems' => 5],
                [ObjectStorage::class, 'array'],
                ObjectStorage::class,
            ],
            'category multiple' => [
                ['type' => 'category'],
                [ObjectStorage::class, 'array'],
                ObjectStorage::class,
            ],
            'inline single' => [
                ['type' => 'inline', 'maxitems' => 1],
                ['object'],
                'object',
            ],
            'inline multiple' => [
                ['type' => 'inline', 'maxitems' => 10],
                [ObjectStorage::class, 'array'],
                ObjectStorage::class,
            ],
            'select with foreign table single' => [
                ['type' => 'select', 'foreign_table' => 'tx_myext_domain_model_parent', 'maxitems' => 1],
                ['object', 'int'],
                'object',
            ],
            'select with foreign table multiple' => [
                ['type' => 'select', 'foreign_table' => 'tx_myext_domain_model_parent', 'maxitems' => 5],
                [ObjectStorage::class, 'array'],
                ObjectStorage::class,
            ],
            'select static multiple' => [
                ['type' => 'select', 'maxitems' => 3, 'items' => [['label' => 'A', 'value' => 'a']]],
                ['array', 'string'],
                'array',
            ],
            'select static single numeric' => [
                ['type' => 'select', 'maxitems' => 1, 'items' => [['label' => 'Low', 'value' => 1], ['label' => 'High', 'value' => 2]]],
                ['int', 'string'],
                'int',
            ],
            'select static single string' => [
                ['type' => 'select', 'maxitems' => 1, 'items' => [['label' => 'Draft', 'value' => 'draft']]],
                ['string', 'int'],
                'string',
            ],
            'group single' => [
                ['type' => 'group', 'maxitems' => 1],
                ['object', 'int', 'string'],
                'object',
            ],
            'group multiple' => [
                ['type' => 'group', 'maxitems' => 5],
                [ObjectStorage::class, 'array', 'string'],
                ObjectStorage::class,
            ],
            'select checkbox multiple by rendertype' => [
                ['type' => 'select', 'renderType' => 'selectCheckBox', 'items' => [['label' => 'A', 'value' => 'a']]],
                ['array', 'string'],
                'array',
            ],
            'select multiple side by side with foreign table' => [
                ['type' => 'select', 'renderType' => 'selectMultipleSideBySide', 'foreign_table' => 'tx_myext_domain_model_tag'],
                [ObjectStorage::class, 'array'],
                ObjectStorage::class,
            ],
            'slug' => [
                ['type' => 'slug'],
                ['string'],
                'string',
            ],
            'uuid' => [
                ['type' => 'uuid'],
                ['string'],
                'string',
            ],
            'radio numeric' => [
                ['type' => 'radio', 'items' => [['label' => 'A', 'value' => 1], ['label' => 'B', 'value' => 2]]],
                ['int', 'string'],
                'int',
            ],
            'radio string' => [
                ['type' => 'radio', 'items' => [['label' => 'A', 'value' => 'opt_a']]],
                ['string', 'int'],
                'string',
            ],
            'language' => [
                ['type' => 'language'],
                ['int', 'string'],
                'int',
            ],
            'flex' => [
                ['type' => 'flex'],
                ['string', 'array'],
                'string',
            ],
            'folder' => [
                ['type' => 'folder'],
                ['string'],
                'string',
            ],
            'imageManipulation' => [
                ['type' => 'imageManipulation'],
                ['string'],
                'string',
            ],
            'passthrough' => [
                ['type' => 'passthrough'],
                ['string', 'int', 'float', 'bool'],
                'string',
            ],
            'none' => [
                ['type' => 'none'],
                ['string', 'int', 'float', 'bool'],
                'string',
            ],
            'unknown type' => [
                ['type' => 'custom_unknown'],
                ModelPropertyType::values(),
                'array',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $columnConfig
     * @param list<string> $expectedTypes
     */
    #[Test]
    #[DataProvider('tcaConfigToTypesDataProvider')]
    public function resolvePropertyTypesReturnsExpectedTypes(
        array $columnConfig,
        array $expectedTypes,
        string $expectedDefault
    ): void {
        $types = $this->subject->resolvePropertyTypes($columnConfig);
        self::assertSame($expectedTypes, $types);
        self::assertSame($expectedDefault, $this->subject->getDefaultPropertyType($columnConfig));
    }

    #[Test]
    public function resolveDefaultValueResolvesBooleanTcaDefaults(): void
    {
        $configZero = ['type' => 'check', 'default' => 0];
        $configOne = ['type' => 'check', 'default' => 1];
        $configFalse = ['type' => 'check', 'default' => '0'];

        self::assertFalse($this->subject->resolveDefaultValue($configZero, ModelPropertyType::BOOL));
        self::assertTrue($this->subject->resolveDefaultValue($configOne, ModelPropertyType::BOOL));
        self::assertFalse($this->subject->resolveDefaultValue($configFalse, ModelPropertyType::BOOL));
    }

    #[Test]
    public function resolveDefaultValueResolvesIntegerTcaDefaults(): void
    {
        $config = ['type' => 'number', 'default' => 42];
        self::assertSame(42, $this->subject->resolveDefaultValue($config, ModelPropertyType::INT));
    }

    #[Test]
    public function resolveDefaultValueResolvesFloatTcaDefaults(): void
    {
        $config = ['type' => 'number', 'format' => 'decimal', 'default' => 19.99];
        self::assertSame(19.99, $this->subject->resolveDefaultValue($config, ModelPropertyType::FLOAT));
    }

    #[Test]
    public function resolveDefaultValueResolvesStringTcaDefaults(): void
    {
        $config = ['type' => 'input', 'default' => 'Default Name'];
        self::assertSame('Default Name', $this->subject->resolveDefaultValue($config, ModelPropertyType::STRING));
    }

    #[Test]
    public function resolveDefaultValueReturnsNullForNonDefaultableTypes(): void
    {
        $config = ['type' => 'file', 'default' => 'some_file'];
        self::assertNull($this->subject->resolveDefaultValue($config, ModelPropertyType::OBJECT_STORAGE));
        self::assertNull($this->subject->resolveDefaultValue($config, ModelPropertyType::DATE_TIME));
        self::assertNull($this->subject->resolveDefaultValue($config, ModelPropertyType::OBJECT));
    }

    #[Test]
    public function resolveDefaultValueFallsBackToTypeSuggestedDefaultWhenNoTcaDefault(): void
    {
        $config = ['type' => 'number'];
        self::assertSame(0, $this->subject->resolveDefaultValue($config, ModelPropertyType::INT));
    }
}
