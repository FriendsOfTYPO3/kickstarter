<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Context;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

readonly class CommandContext
{
    private SymfonyStyle $io;

    public function __construct(
        private InputInterface $input,
        private OutputInterface $output,
    ) {
        $this->io = new SymfonyStyle($input, $output);
    }

    public function getIo(): SymfonyStyle
    {
        return $this->io;
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }
}
