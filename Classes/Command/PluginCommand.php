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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\PluginNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\PluginInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\PluginCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCommand extends Command
{
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use FileStructureBuilderTrait;

    public function __construct(
        private readonly PluginCreatorService $pluginCreatorService,
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
            'We are here to assist you in creating a new TYPO3 plugin.',
            'Now, we will ask you a few questions to customize the plugin according to your needs.',
            'Please take your time to answer them.',
        ]);

        $pluginInformation = $this->askForPluginInformation($commandContext);
        $this->pluginCreatorService->create($pluginInformation);
        $this->printCreatorInformation($pluginInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForPluginInformation(CommandContext $commandContext): PluginInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );
        $pluginLabel = (string)$io->ask(
            'Please provide a label for your plugin. You will see the label in the backend',
        );

        $pluginName = (string)$this->questionCollection->askQuestion(
            PluginNameQuestion::ARGUMENT_NAME,
            $commandContext,
            $pluginLabel
        );

        $pluginDescription = (string)$io->ask(
            'Please provide a short plugin description. You will see it in new content element wizard',
        );

        $referencedControllerActions = [];
        $isTypoScriptCreation = false;
        $typoScriptSet = null;
        $isExtbasePlugin = $io->confirm('Do you prefer to create an extbase based plugin?');
        $templatePath = '';
        if ($isExtbasePlugin) {
            $extbaseControllerClassnames = $extensionInformation->getExtbaseControllerClassnames();
            if ($extbaseControllerClassnames === []) {
                $io->error([
                    'Your extension does not contain any extbase controllers.',
                    'Please create at least one extbase controller with \'typo3 make:controller\' before creating a plugin.',
                ]);
                die();
            }

            $referencedControllerActions = $this->askForReferencedControllerActions(
                $commandContext,
                $extbaseControllerClassnames,
                $extensionInformation,
            );
            $pluginCType = sprintf('tx_%s_%s', str_replace('_', '', $extensionInformation->getExtensionKey()), strtolower($pluginName));
            $isTypoScriptCreation = $io->confirm(sprintf('Do you want to create the default TypoScript for %s?', $pluginCType));
            if ($isTypoScriptCreation) {
                $setOptions = array_merge([$extensionInformation->getDefaultTypoScriptPath()], $extensionInformation->getSets());

                // Ask user to choose one (no default)
                $typoScriptSet = $io->choice(
                    'To which set (site set or default path) do you want to add the TypoScript?',
                    $setOptions,
                );

                $templatePath = $io->ask(
                    'To which path do you want to add the Fluid templates?',
                    sprintf('EXT:%s/Resources/Private/', $extensionInformation->getExtensionKey())
                );
            }
        }

        return new PluginInformation(
            $extensionInformation,
            $isExtbasePlugin,
            $pluginLabel,
            $pluginName,
            $pluginDescription,
            $referencedControllerActions,
            new CreatorInformation(),
            $isTypoScriptCreation,
            $typoScriptSet,
            $templatePath,
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

            $referencedControllerActions[$referencedExtbaseControllerName]['cached'] = $io->choice(
                'Select the CACHED actions for your controller ' . $referencedExtbaseControllerName . ' you want to reference to your plugin',
                $extbaseControllerActionNames,
                null,
                true
            );
            if (in_array($skipAction, $referencedControllerActions[$referencedExtbaseControllerName]['cached'])) {
                $referencedControllerActions[$referencedExtbaseControllerName]['cached'] = [];
            }

            $referencedControllerActions[$referencedExtbaseControllerName]['uncached'] = $io->choice(
                'Select the UNCACHED actions for your controller ' . $referencedExtbaseControllerName . ' you want to reference to your plugin',
                $extbaseControllerActionNames,
                null,
                true
            );
            if (in_array($skipAction, $referencedControllerActions[$referencedExtbaseControllerName]['uncached'])) {
                $referencedControllerActions[$referencedExtbaseControllerName]['uncached'] = [];
            }
        }

        return $referencedControllerActions;
    }
}
