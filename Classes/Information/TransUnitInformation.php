<?php

namespace FriendsOfTYPO3\Kickstarter\Information;

final class TransUnitInformation
{
    public function __construct(
        private string $id,
        private string $source,
        private ?string $target = null,
    ) {
        $this->id = trim($this->id);
        if ($this->id === '') {
            throw new \InvalidArgumentException('trans-unit id must not be empty.');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    public function setTarget(?string $target): void
    {
        $this->target = $target;
    }
}
