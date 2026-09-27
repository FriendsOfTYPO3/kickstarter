<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Information;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\TypeConverterInformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypeConverterInformationTest extends FunctionalTestCase
{
    private ExtensionInformation $extensionInformation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensionInformation = $this->createMock(ExtensionInformation::class);
    }

    #[Test]
    public function constructWithValidSourceTypes(): void
    {
        $information = new TypeConverterInformation(
            $this->extensionInformation,
            'ItemConverter',
            10,
            'string, array, float, integer, boolean',
            'int',
        );

        self::assertSame('string, array, float, integer, boolean', $information->getSource());
        self::assertSame(['string', 'array', 'float', 'integer', 'boolean'], $information->getSourceTypes());
    }

    #[Test]
    public function constructNormalizesAliasesAndDeduplicates(): void
    {
        $information = new TypeConverterInformation(
            $this->extensionInformation,
            'ItemConverter',
            10,
            '  int , bool , string, INTEGER, Bool ',
            'int',
        );

        self::assertSame('integer, boolean, string', $information->getSource());
        self::assertSame(['integer', 'boolean', 'string'], $information->getSourceTypes());
    }

    #[Test]
    public function constructThrowsExceptionWhenSourceIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1758920001);

        new TypeConverterInformation(
            $this->extensionInformation,
            'ItemConverter',
            10,
            '  ,  ',
            'int',
        );
    }

    #[Test]
    public function constructThrowsExceptionWhenInvalidSourceTypesProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1758920002);
        $this->expectExceptionMessage('Invalid source type(s) "datetime", "object"');

        new TypeConverterInformation(
            $this->extensionInformation,
            'ItemConverter',
            10,
            'DateTime, string, object',
            'int',
        );
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function sourceTypesDataProvider(): array
    {
        return [
            'single string' => ['string', ['string']],
            'aliases int and bool' => ['int, bool', ['integer', 'boolean']],
            'mixed case and spacing' => [' String , FLOAT , Array ', ['string', 'float', 'array']],
            'duplicates' => ['int, integer, INT', ['integer']],
        ];
    }

    #[Test]
    #[DataProvider('sourceTypesDataProvider')]
    public function parseAndNormalizeSourceTypes(string $input, array $expected): void
    {
        self::assertSame($expected, TypeConverterInformation::parseAndNormalizeSourceTypes($input));
    }

    #[Test]
    public function findInvalidSourceTypesReturnsOnlyInvalid(): void
    {
        $invalid = TypeConverterInformation::findInvalidSourceTypes(['string', 'invalid_type', 'integer', 'another_bad']);
        self::assertSame(['invalid_type', 'another_bad'], $invalid);
    }
}
