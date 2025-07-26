<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Command;

use StefanFroemken\ExtKickstarter\Information\ExtensionInformation;
use StefanFroemken\ExtKickstarter\Information\ModuleInformation;
use StefanFroemken\ExtKickstarter\Service\Creator\ModuleCreatorService;
use StefanFroemken\ExtKickstarter\Traits\AskForExtensionKeyTrait;
use StefanFroemken\ExtKickstarter\Traits\ExtensionInformationTrait;
use StefanFroemken\ExtKickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ModuleCommand extends Command
{
    use AskForExtensionKeyTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly ModuleCreatorService $moduleCreatorService,
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
        $io = new SymfonyStyle($input, $output);
        $io->title('Welcome to the TYPO3 Extension Builder');

        $io->text([
            'We are here to assist you in creating a new TYPO3 module.',
            'Now, we will ask you a few questions to customize the module according to your needs.',
            'Please take your time to answer them.',
        ]);

        $this->moduleCreatorService->create($this->askForModuleInformation($io, $input));

        return Command::SUCCESS;
    }

    private function askForModuleInformation(SymfonyStyle $io, InputInterface $input): ModuleInformation
    {
        $extensionInformation = $this->getExtensionInformation(
            $this->askForExtensionKey($io, $input->getArgument('extension_key')),
            $io
        );

        $parentModule = (string)$io->choice(
            'If the module should be a submodule, the parent identifier, for example "web" has to be set here',
            ['web', 'site', 'file', 'tools', 'system'],
            'web',
        );

        $extensionName = (string)$io->ask(
            'The extension name in UpperCamelCase for which the module is registered',
            $extensionInformation->getExtensionName(),
        );

        return new ModuleInformation(
            $extensionInformation,
            (string)$io->ask('Define an module identifier', $parentModule . '_' . $extensionName),
            $parentModule,
            (string)$io->choice(
                'The module position. Allowed values are "top" and "bottom"',
                ['bottom', 'top'],
                'bottom',
            ),
            (string)$io->choice(
                'Define access. Can be "user" (editor permissions), "admin", or "systemMaintainer"',
                ['user', 'admin', 'systemMaintainer'],
                'user'
            ),
            (string)$io->choice(
                'Define workspaces',
                ['*', 'live', 'offline'],
                '*'
            ),
            (string)$io->ask(
                'Define the path to the default endpoint',
                sprintf(
                    '/module/%s/%s',
                    $parentModule,
                    strtolower($extensionName),
                )
            ),
            (string)$io->ask('Define module title', ''),
            (string)$io->ask('Define module description', ''),
            (string)$io->ask('Define module short description', ''),
            (string)$io->ask('The module icon identifier', ''),
            $io->confirm('Do you prefer to reference extbase controller actions or native routes'),
            $extensionName,
            $this->askForReferencedControllerActions($io, $extensionInformation->getExtbaseControllerClassnames(), $extensionInformation),
            $this->askForNativeRoutes($io, $extensionInformation->getControllerClassnames(), $extensionInformation),
        );
    }

    private function askForReferencedControllerActions(
        SymfonyStyle $io,
        array $extbaseControllerClassnames,
        ExtensionInformation $extensionInformation,
    ): array {
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
        SymfonyStyle $io,
        array $controllerClassnames,
        ExtensionInformation $extensionInformation,
    ): array {
        return [];
    }
}
