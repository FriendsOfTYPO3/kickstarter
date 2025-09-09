<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Tca\Table;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\TableInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TcaTableCreator implements TcaTableCreatorInterface
{
    use FileStructureBuilderTrait;

    private const FIELDS_GENERAL = '###FIELDS_GENERAL###';

    private BuilderFactory $factory;

    public function __construct(
        private readonly FileManager $fileManager,
    ) {
        $this->factory = new BuilderFactory();
    }

    public function create(TableInformation $tableInformation): void
    {
        GeneralUtility::mkdir_deep($tableInformation->getTableConfigurationPath());

        $tableFile = $tableInformation->getFullTableConfigurationFilePath();
        $fileStructure = $this->buildFileStructure($tableFile);

        if (!is_file($tableFile)) {
            $this->addTableNode($fileStructure, $tableInformation);
        }

        $columnsItem = $this->getExistingTcaSection($fileStructure, 'columns');
        if ($columnsItem instanceof ArrayItem && $columnsItem->value instanceof Array_) {
            $columnsItem->value->items = $this->addNewTcaColumns($columnsItem->value->items, $tableInformation);
        }

        $typesItem = $this->getExistingTcaSection($fileStructure, 'types');
        if ($typesItem instanceof ArrayItem && $typesItem->value instanceof Array_) {
            $this->addNewTcaTypeItems($typesItem->value->items, $tableInformation);
        }

        $this->fileManager->createOrModifyFile($tableFile, $fileStructure->getFileContents(), $tableInformation->getCreatorInformation());
    }

    private function getExistingTcaSection(FileStructure $fileStructure, string $section): ?ArrayItem
    {
        if ($fileStructure->getReturnStructures()->count() === 0) {
            return null;
        }

        /** @var ReturnStructure $returnStructure */
        $returnStructure = $fileStructure->getReturnStructures()->current();

        if (($expr = $returnStructure->getNode()->expr) && $expr instanceof Array_) {
            foreach ($expr->items as $item) {
                if ($item->key instanceof String_ && $item->key->value === $section) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function addNewTcaColumns(array $existingTcaColumns, TableInformation $tableInformation): array
    {
        $existingColumnNames = [];
        foreach ($existingTcaColumns as $existingTcaColumn) {
            $existingColumnNames[] = $existingTcaColumn->key->value;
        }

        foreach ($tableInformation->getColumns() as $columnName => $columnConfiguration) {
            if (in_array($columnName, $existingColumnNames, true)) {
                continue;
            }

            $existingTcaColumns[] = new ArrayItem($this->factory->val([
                'exclude' => true,
                'label' => $columnConfiguration['label'],
                'config' => $columnConfiguration['config'],
            ]), new String_($columnName));
        }

        return $existingTcaColumns;
    }

    /**
     * Adds types -> showitems, for example:
     *
     * ```
     * 'types' => [
     *   [
     *     'showitem' => 'hidden, sys_language_uid, l10n_diffsource, test',
     *   ],
     * ],
     * ```
     */
    private function addNewTcaTypeItems(array $existingTcaTypes, TableInformation $tableInformation): void
    {
        if (!isset($existingTcaTypes[0]->value->items[0]->value)) {
            return;
        }

        /** @var String_ $showItems */
        $showItems = $existingTcaTypes[0]->value->items[0]->value;
        $showItemsString = $showItems->value;

        $existingFieldNames = GeneralUtility::trimExplode(',', $showItemsString);

        // Collect fields to add
        $newFields = [];
        foreach (array_keys($tableInformation->getColumns()) as $columnName) {
            if (!in_array($columnName, $existingFieldNames, true)) {
                $newFields[] = $columnName;
            }
        }

        if ($newFields === []) {
            return; // Nothing to add
        }

        // Step 1: Placeholder logic
        $placeholder = self::FIELDS_GENERAL;
        if (str_contains($showItemsString, $placeholder)) {
            $showItems->value = str_replace($placeholder, implode(', ', $newFields) . ',', $showItemsString);
            return;
        }

        // Step 2: Split by --div-- and insert into first tab
        $tabs = preg_split('/(\s*--div--;[^,]+,\s*)/', $showItemsString, -1, PREG_SPLIT_DELIM_CAPTURE);

        $inserted = false;
        $counter = count($tabs);
        for ($i = 0; $i < $counter; $i++) {
            if (!$inserted && str_starts_with(trim($tabs[$i]), '--div--')   && isset($tabs[$i + 1])) {
                $existingFields = GeneralUtility::trimExplode(',', $tabs[$i + 1], true);

                // Add only new fields
                foreach ($newFields as $field) {
                    if (!in_array($field, $existingFields, true)) {
                        $existingFields[] = $field;
                    }
                }

                $tabs[$i + 1] = implode(', ', $existingFields) . ',';
                $inserted = true;
            }
        }

        if ($inserted) {
            $showItems->value = implode('', $tabs);
        } else {
            // No tabs at all: fallback to flat list
            $existingFields = GeneralUtility::trimExplode(',', $showItemsString, true);

            foreach ($newFields as $field) {
                if (!in_array($field, $existingFields, true)) {
                    $existingFields[] = $field;
                }
            }

            $showItems->value = implode(', ', $existingFields);
        }
    }

    private function addTableNode(FileStructure $fileStructure, TableInformation $tableInformation): void
    {
        $fileStructure->addReturnStructure(
            new ReturnStructure(
                new Return_(
                    new Array_([
                        new ArrayItem($this->getCtrlArrayItems($tableInformation), new String_('ctrl')),
                        new ArrayItem($this->getTypesArrayItems(), new String_('types')),
                        new ArrayItem($this->getPalettesArrayItems(), new String_('palettes')),
                        new ArrayItem($this->getColumnsArrayItems($tableInformation), new String_('columns')),
                    ])
                )
            )
        );
    }

    private function getCtrlArrayItems(TableInformation $tableInformation): Expr
    {
        return $this->factory->val([
            'title' => $tableInformation->getTitle(),
            'label' => $tableInformation->getLabel(),
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'versioningWS' => true,
            'origUid' => 't3_origuid',
            'languageField' => 'sys_language_uid',
            'transOrigPointerField' => 'l10n_parent',
            'transOrigDiffSourceField' => 'l10n_diffsource',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
                'starttime' => 'starttime',
                'endtime' => 'endtime',
            ],
        ]);
    }

    private function getTypesArrayItems(): Expr
    {
        return $this->factory->val([
            '0' => [
                'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    ' . self::FIELDS_GENERAL . '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,--palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            ],
        ]);
    }

    private function getPalettesArrayItems(): Expr
    {
        return $this->factory->val([
            'access' => [
                'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
            ],
            'language' => [
                'showitem' => 'sys_language_uid, l10n_parent',
            ],
        ]);
    }

    private function getColumnsArrayItems(TableInformation $tableInformation): Expr
    {
        return $this->factory->val([
            'sys_language_uid' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
                'config' => [
                    'type' => 'language',
                ],
            ],
            'l10n_parent' => [
                'displayCond' => 'FIELD:sys_language_uid:>:0',
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
                'config' => [
                    'type' => 'group',
                    'allowed' => $tableInformation->getTableName(),
                    'size' => 1,
                    'maxitems' => 1,
                    'minitems' => 0,
                    'default' => 0,
                    'suggestOptions' => [
                        'default' => [
                            'searchWholePhrase' => true,
                            'addWhere' => 'AND ' . $tableInformation->getTableName() . '.sys_language_uid IN (0,-1)',
                        ],
                    ],
                ],
            ],
            'l10n_diffsource' => [
                'config' => [
                    'type' => 'passthrough',
                    'default' => '',
                ],
            ],
            'hidden' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        [
                            'label' => '',
                            'invertStateDisplay' => true,
                        ],
                    ],
                ],
            ],
            'starttime' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'default' => 0,
                ],
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
            ],
            'endtime' => [
                'exclude' => true,
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'datetime',
                    'default' => 0,
                    'range' => [
                        'upper' => mktime(0, 0, 0, 1, 1, 2038),
                    ],
                ],
                'l10n_mode' => 'exclude',
                'l10n_display' => 'defaultAsReadonly',
            ],
        ]);
    }
}
