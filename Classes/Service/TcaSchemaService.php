<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service;

use Doctrine\DBAL\Schema\Table;
use FriendsOfTYPO3\Kickstarter\Information\ExtTablesSqlInformation;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Utility\MathUtility;

class TcaSchemaService
{
    public function getExtTablesSqlInformationBasedOnTca(
        string $columnType,
        array $fieldConfig
    ): ?ExtTablesSqlInformation {
        $extTablesSqlInformation = null;

        switch ($columnType) {
            case 'category':
                if (($fieldConfig['relationship'] ?? '') === 'oneToMany') {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'TEXT',
                        false,
                    );
                } else {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'INT',
                        true,
                        true,
                        0,
                    );
                }
                break;

            case 'datetime':
                $dbType = $fieldConfig['dbType'] ?? '';
                // Add datetime fields for all tables, defining datetime columns (TCA type=datetime), except
                // those columns, which had already been added due to definition in "ctrl", e.g. "starttime".
                if (in_array($dbType, QueryHelper::getDateTimeTypes(), true)) {
                    $nullable = $fieldConfig['nullable'] ?? true;
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        $dbType,
                        // native datetime fields are nullable by default, and
                        // are only not-nullable if `nullable` is explicitly set to false.
                        !$nullable,
                    );
                } else {
                    // int unsigned:            from 1970 to 2106.
                    // int signed:              from 1901 to 2038.
                    // bigint unsigned/signed:  from whenever to whenever
                    //
                    // Anything like crdate,tstamp,starttime,endtime is good with
                    //  "int unsigned" and can survive the 2038 apocalypse (until 2106).
                    //
                    // However, anything that has birthdates or dates
                    // from the past (sys_file_metadata.content_creation_date) was saved
                    // as a SIGNED INT. It allowed birthdays of people older than 1970,
                    // but with the downside that it ends in 2038.
                    //
                    // This is now changed to utilize BIGINT everywhere, even when smaller
                    // date ranges are requested. To reduce complexity, we specifically
                    // do not evaluate "range.upper/lower" fields and use a unified type here.
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'BIGINT',
                        !($fieldConfig['nullable'] ?? false),
                        false,
                        0
                    );
                }
                break;

            case 'slug':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'TEXT',
                    false,
                    false,
                    null,
                    65535
                );
                break;

            case 'json':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'JSON',
                    false,
                );
                break;

            case 'uuid':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'JSON',
                    true,
                    false,
                    '',
                    36
                );
                break;

            case 'file':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'INT',
                    true,
                    true,
                    0
                );
                break;

            case 'folder':
            case 'imageManipulation':
            case 'flex':
            case 'text':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'TEXT',
                    false,
                );
                break;

            case 'email':
                $isNullable = (bool)($fieldConfig['nullable'] ?? false);
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'VARCHAR',
                    !$isNullable,
                    false,
                    ($isNullable ? null : ''),
                    255
                );
                break;

            case 'check':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'SMALLINT',
                    true,
                    true,
                    (int)($fieldConfig['default'] ?? 0)
                );
                break;

            case 'language':
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'INT',
                    true,
                    false,
                    0
                );
                break;

            case 'group':
                if (isset($fieldConfig['MM'])) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'INT',
                        true,
                        true,
                        0
                    );
                } else {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'TEXT',
                        false,
                    );
                }
                break;

            case 'password':
                if ($fieldConfig['nullable'] ?? false) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        false,
                        false
                    );
                } else {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        ''
                    );
                }
                break;

            case 'color':
                $opacity = (bool)($fieldConfig['opacity'] ?? false);
                if ($fieldConfig['nullable'] ?? false) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        false,
                        false,
                        null,
                        $opacity ? 9 : 7
                    );
                } else {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        '',
                        $opacity ? 9 : 7
                    );
                }
                break;

            case 'radio':
                $hasItemsProcFunc = ($fieldConfig['itemsProcFunc'] ?? '') !== '';
                $items = $fieldConfig['items'] ?? [];
                // With itemsProcFunc we can't be sure, which values are persisted. Use type string.
                if ($hasItemsProcFunc) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        '',
                        255
                    );
                    break;
                }
                // If no items are configured, use type string to be safe for values added directly.
                if ($items === []) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        '',
                        255
                    );
                    break;
                }
                // If only one value is NOT an integer use type string.
                foreach ($items as $item) {
                    if (!MathUtility::canBeInterpretedAsInteger($item['value'])) {
                        $extTablesSqlInformation = new ExtTablesSqlInformation(
                            $fieldConfig['columnName'],
                            'VARCHAR',
                            true,
                            false,
                            '',
                            255
                        );
                        // continue with next $tableDefinition['columns']
                        // see: DefaultTcaSchemaTest->enrichAddsRadioStringVerifyThatCorrectLoopIsContinued()
                        break 2;
                    }
                }
                // Use integer type.
                $allValues = array_map(fn(array $item): int => (int)$item['value'], $items);
                $minValue = min($allValues);
                $maxValue = max($allValues);
                // Try to safe some bytes - can be reconsidered to simply use 'INT'.
                $integerType = ($minValue >= -32768 && $maxValue < 32768)
                    ? 'SMALLINT'
                    : 'INT';
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    $integerType,
                    true,
                    false,
                    null,
                    0
                );
                break;

            case 'link':
                $nullable = $fieldConfig['nullable'] ?? false;
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'TEXT',
                    !$nullable,
                    false,
                    $nullable ? null : '',
                    65535
                );
                break;

            case 'input':
                $length = (int)($fieldConfig['max'] ?? 255);
                $nullable = $fieldConfig['nullable'] ?? false;
                if ($length > 255) {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'TEXT',
                        !$nullable,
                        false,
                        $nullable ? null : '',
                        65535
                    );
                    break;
                }
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'VARCHAR',
                    !$nullable,
                    false,
                    '',
                    $length
                );
                break;

            case 'inline':
                if (($fieldConfig['MM'] ?? '') !== '' || ($fieldConfig['foreign_field'] ?? '') !== '') {
                    // Parent "count" field
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'INT',
                        true,
                        true,
                        0
                    );
                } else {
                    // Inline "csv"
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        '',
                        255
                    );
                }
                break;

            case 'number':
                $type = ($fieldConfig['format'] ?? '') === 'decimal' ? 'DECIMAL' : 'INT';
                $nullable = $fieldConfig['nullable'] ?? false;
                $lowerRange = $fieldConfig['range']['lower'] ?? -1;
                // Integer type for all database platforms.
                if ($type === 'INT') {
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'INT',
                        !$nullable,
                        $lowerRange >= 0,
                        $nullable === true ? null : 0
                    );
                    break;
                }

                // Decimal for all supported platforms except SQLite
                $extTablesSqlInformation = new ExtTablesSqlInformation(
                    $fieldConfig['columnName'],
                    'DECIMAL',
                    !$nullable,
                    $lowerRange >= 0,
                    $nullable === true ? null : 0.00,
                    null,
                    10,
                    2
                );
                break;

            case 'select':
                if (($fieldConfig['MM'] ?? '') !== '') {
                    // MM relation, this is a "parent count" field. Have an int.
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'INT',
                        true,
                        true,
                        0
                    );
                    break;
                }
                $dbFieldLength = (int)($fieldConfig['dbFieldLength'] ?? 0);
                // If itemsProcFunc is not set, check the item values
                if (($fieldConfig['itemsProcFunc'] ?? '') === '') {
                    $items = $fieldConfig['items'] ?? [];
                    $itemsContainsOnlyIntegers = true;
                    foreach ($items as $item) {
                        if (!MathUtility::canBeInterpretedAsInteger($item['value'])) {
                            $itemsContainsOnlyIntegers = false;
                            break;
                        }
                    }
                    $itemsAreAllPositive = true;
                    foreach ($items as $item) {
                        if ($item['value'] < 0) {
                            $itemsAreAllPositive = false;
                            break;
                        }
                    }
                    // @todo: The dependency to renderType is unfortunate here. It's only purpose is to potentially have int fields
                    //        instead of string when this is a 'single' relation / value. However, renderType should usually not
                    //        influence DB layer at all. Maybe 'selectSingle' should be changed to an own 'type' instead to make
                    //        this more explicit. Maybe DataHandler could benefit from this as well?
                    if (($fieldConfig['renderType'] ?? '') === 'selectSingle' || ($fieldConfig['maxitems'] ?? 0) === 1) {
                        // With 'selectSingle' or with 'maxitems = 1', only a single value can be selected.
                        if (
                            !is_array($fieldConfig['fileFolderConfig'] ?? false)
                            && ($items !== [] || ($fieldConfig['foreign_table'] ?? '') !== '')
                            && $itemsContainsOnlyIntegers
                        ) {
                            // If the item list is empty, or if it contains only int values, an int field is enough.
                            // Also, the config must not be a 'fileFolderConfig' field which takes string values.
                            $extTablesSqlInformation = new ExtTablesSqlInformation(
                                $fieldConfig['columnName'],
                                'INT',
                                true,
                                $itemsAreAllPositive,
                                0
                            );
                            break;
                        }
                        // If int is no option, have a string field.
                        $extTablesSqlInformation = new ExtTablesSqlInformation(
                            $fieldConfig['columnName'],
                            'VARCHAR',
                            true,
                            false,
                            '',
                            $dbFieldLength > 0 ? $dbFieldLength : 255
                        );
                        break;
                    }
                    if ($itemsContainsOnlyIntegers) {
                        // Multiple values can be selected and will be stored comma separated. When manual item values are
                        // all integers, or if there is a foreign_table, we end up with a comma separated list of integers.
                        // Using string / varchar 255 here should be long enough to store plenty of values, and can be
                        // changed by setting 'dbFieldLength'.
                        $extTablesSqlInformation = new ExtTablesSqlInformation(
                            $fieldConfig['columnName'],
                            'VARCHAR',
                            // @todo: nullable = true is not a good default here. This stems from the fact that this
                            //        if triggers a lot of TEXT->VARCHAR() field changes during upgrade, where TEXT
                            //        is always nullable, but varchar() is not. As such, we for now declare this
                            //        nullable, but could have a look at it later again when a value upgrade
                            //        for such cases is in place that updates existing null fields to empty string.
                            false,
                            false,
                            '',
                            $dbFieldLength > 0 ? $dbFieldLength : 255
                        );
                        break;
                    }
                }
                if ($dbFieldLength > 0) {
                    // If nothing else matches, but there is a dbFieldLength set, have varchar with that length.
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'VARCHAR',
                        true,
                        false,
                        '',
                        $dbFieldLength
                    );
                } else {
                    // Final fallback creates a (nullable) text field.
                    $extTablesSqlInformation = new ExtTablesSqlInformation(
                        $fieldConfig['columnName'],
                        'TEXT',
                        false,
                    );
                }
                break;
        }

        return $extTablesSqlInformation;
    }

    /**
     * True if an index with a given name is defined within the incoming
     * array of Table's.
     *
     * @param array<non-empty-string, Table> $tables
     */
    protected function isIndexDefinedForTable(array $tables, string $tableName, string $indexName): bool
    {
        return ($tables[$tableName] ?? null)?->hasIndex($indexName) ?? false;
    }
}
