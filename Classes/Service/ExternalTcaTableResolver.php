<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service;

use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final readonly class ExternalTcaTableResolver
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory
    ) {}

    /**
     * Returns TCA tables that can reasonably be mapped to a domain model:
     * - not from the current extension
     * - not sys_* or be_* tables
     * - not rootLevel = 1
     * - not hideTable = 1
     */
    public function getExternalTcaTables(ExtensionInformation $extension): array
    {
        /** @var array<string, TcaSchema> $schemas */
        $schemas = iterator_to_array($this->tcaSchemaFactory->all());

        $extensionTables = $extension->getConfiguredTcaTables();
        $result = [];

        foreach ($schemas as $tableName => $schema) {
            // Skip local extension tables
            if (in_array($tableName, $extensionTables, true)) {
                continue;
            }

            // Skip system and backend tables
            if (str_starts_with($tableName, 'sys_')) {
                continue;
            }
            if (str_starts_with($tableName, 'be_')) {
                continue;
            }

            if ($schema->hasCapability(TcaSchemaCapability::HideInUi)) {
                continue;
            }

            if ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
                continue;
            }

            $result[] = $tableName;
        }

        sort($result);
        return $result;
    }
}
