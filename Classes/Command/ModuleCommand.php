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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ModuleIdentifierQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\ModuleParentQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\ModuleInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\ModuleCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ModuleCommand extends Command
{
    use AskForExtensionKeyTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ModuleCreatorService $moduleCreatorService,
        private readonly QuestionCollection $questionCollection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'extension_key',
            InputArgument::OPTIONAL,
            'Provide the extension key you want to extend.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandContext = new CommandContext($input, $output);
        $io = $commandContext->getIo();
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'We are here to assist you in creating a new TYPO3 module.',
            'Now, we will ask you a few questions to customize the module according to your needs.',
            'Please take your time to answer them.',
        ]);

        $this->moduleCreatorService->create($this->askForModuleInformation($commandContext));

        return Command::SUCCESS;
    }

    private function askForModuleInformation(CommandContext $commandContext): ModuleInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $moduleType = $io->confirm('Do you prefer to reference Extbase controller actions or native routes');
        $extbaseControllers = $extensionInformation->getExtbaseControllerClassnames();
        if ($moduleType && $extbaseControllers === []) {
            $io->error('To create an Extbase backend module, create an Extbase Controller first, using command make:controller.');
            die();
        }

        $parentModule = (string)$this->questionCollection->askQuestion(
            ModuleParentQuestion::ARGUMENT_NAME,
            $commandContext
        );

        $extensionName = GeneralUtility::underscoredToUpperCamelCase($extensionInformation->getExtensionKey());

        $identifier  = (string)$this->questionCollection->askQuestion(
            ModuleIdentifierQuestion::ARGUMENT_NAME,
            $commandContext,
            $parentModule . '_' . $extensionName
        );

        return new ModuleInformation(
            extensionInformation: $extensionInformation,
            identifier: $identifier,
            parent: $parentModule,
            position: (string)$io->choice(
                'The module position. Allowed values are "top" and "bottom"',
                ['bottom', 'top'],
                'bottom',
            ),
            access: (string)$io->choice(
                'Define access. Can be "user" (editor permissions), "admin", or "systemMaintainer"',
                ['user', 'admin', 'systemMaintainer'],
                'user'
            ),
            workspaces: (string)$io->choice(
                'Define workspaces',
                ['*', 'live', 'offline'],
                '*'
            ),
            path: (string)$io->ask(
                'Define the path to the default endpoint',
                sprintf(
                    '/module/%s/%s',
                    $parentModule,
                    strtolower($extensionName),
                )
            ),
            title: (string)$io->ask('Define module title', $extensionName),
            description: (string)$io->ask('Define module description', ''),
            shortDescription: (string)$io->ask('Define module short description', ''),
            iconIdentifier: (string)$io->ask('The module icon identifier', ''),
            isExtbaseModule: $moduleType,
            extensionName: $extensionName,
            referencedControllerActions: $this->askForReferencedControllerActions($commandContext, $extbaseControllers, $extensionInformation),
            referencedRoutes: $this->askForNativeRoutes($commandContext, $extensionInformation->getControllerClassnames(), $extensionInformation),
        );
    }

    private function askForReferencedControllerActions(
        CommandContext $commandContext,
        array $extbaseControllerClassnames,
        ExtensionInformation $extensionInformation,
    ): array {
        $io = $commandContext->getIo();
        $skipAction = 'no choice (skip)';
        $referencedControllerActions = [];

        $referencedExtbaseControllerNames = (array)$io->choice(
            'Select the extbase controller classes you want to reference to your plugin',
            $extbaseControllerClassnames,
            null,
            true
        );

        foreach ($referencedExtbaseControllerNames as $referencedExtbaseControllerName) {
            $extbaseControllerActionNames = $extensionInformation->getExtbaseControllerActionNames($referencedExtbaseControllerName);
            $extbaseControllerActionNames[] = $skipAction;

            $referencedControllerActions[$referencedExtbaseControllerName] = $io->choice(
                'Select the actions for your controller ' . $referencedExtbaseControllerName . ' you want to reference to your plugin',
                $extbaseControllerActionNames,
                null,
                true
            );
            if (in_array($skipAction, $referencedControllerActions[$referencedExtbaseControllerName])) {
                $referencedControllerActions[$referencedExtbaseControllerName] = [];
            }
        }

        return $referencedControllerActions;
    }

    private function askForNativeRoutes(
        CommandContext $commandContext,
        array $controllerClassnames,
        ExtensionInformation $extensionInformation,
    ): array {
        return [];
    }
}
