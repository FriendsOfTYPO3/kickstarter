<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\TableInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class TableCreatorService
{
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.tca.table')]
        private iterable $tableCreators,
    ) {}

    public function create(TableInformation $tableInformation): void
    {
        foreach ($this->tableCreators as $creator) {
            $creator->create($tableInformation);
        }
    }
}
