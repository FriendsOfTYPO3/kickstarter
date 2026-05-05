<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Information\RepositoryInformation;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class RepositoryCreatorService
{
    public function __construct(
        #[AutowireIterator('ext-kickstarter.creator.domain.repository')]
        private iterable $repositoryCreators,
    ) {}

    public function create(RepositoryInformation $repositoryInformation): void
    {
        foreach ($this->repositoryCreators as $creator) {
            $creator->create($repositoryInformation);
        }
    }
}
