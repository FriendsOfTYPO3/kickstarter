<?php

namespace FriendsOfTYPO3\Kickstarter\Service\Creator;

use FriendsOfTYPO3\Kickstarter\Creator\Locallang\LocallangCreatorInterface;
use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;

readonly class LocallangCreatorService
{
    /**
     * @param iterable<LocallangCreatorInterface> $locallangCreators
     */
    public function __construct(
        private iterable $locallangCreators,
    ) {}

    public function create(LocallangInformation $locallangInformation): void
    {
        foreach ($this->locallangCreators as $creator) {
            $creator->create($locallangInformation);
        }
    }
}
