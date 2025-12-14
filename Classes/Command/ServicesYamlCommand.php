<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ChooseExtensionKeyQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ServicesConfigInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ServicesConfigCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;

#[AsCommand('make:services-yaml', 'Add or update the services.yaml.')]
#[AsNonSchedulableCommand]
class ServicesYamlCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ServicesConfigCreatorService $servicesConfigCreatorService,
        private readonly QuestionCollection $questionCollection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'extension_key',
            InputArgument::OPTIONAL,
            'Provide the extension key you want to extend',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CommandContext($input, $output);
        $io = $commandContext->getIo();
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'This command creates or updates your projects Services.yaml to enable Dependency Injection (See https://docs.typo3.org/permalink/t3coreapi:extension-configuration-services-yaml).',
        ]);

        $servicesInformation = $this->askForServicesConfigInformation($commandContext);
        $this->servicesConfigCreatorService->create($servicesInformation);
        $this->printCreatorInformation($servicesInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForServicesConfigInformation(CommandContext $commandContext): ServicesConfigInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        if ($io->confirm('Do you want to create a basic Services.yaml with the recommended settings? Choose no to configure it individually')) {
            return new ServicesConfigInformation(
                extensionInformation: $extensionInformation,
            );
        }

        return new ServicesConfigInformation(
            extensionInformation: $extensionInformation,
            autowire: $io->confirm('Do you want to use autowire?', true),
            autoconfigure: $io->confirm('Do you want to use autoconfigure?', true),
            public: $io->confirm('Do you want to use autoconfigure?', false),
            excludeModels: $io->confirm('Do you want to exclude Extbase models?', true),
        );
    }
}
