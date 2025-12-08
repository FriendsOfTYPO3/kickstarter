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
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\RepositoryCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ApplyTypo3CglCommand extends Command
{
    use ExtensionInformationTrait;

    private const DIRECTORIES = [
        'Classes',
        'Configuration',
        'Tests',
    ];

    public function __construct(
        private readonly RepositoryCreatorService $repositoryCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Extbase Repository.',
            'Now, we will ask you a few questions to customize the repository according to your needs.',
            'Please take your time to answer them.',
        ]);

        if (!Environment::isComposerMode()) {
            $io->error('This command requires TYPO3 to be running in Composer mode.');
            return Command::FAILURE;
        }

        if (!$this->isExecAvailable()) {
            $io->error('This command requires exec to be available.');
            return Command::FAILURE;
        }

        $composerManifest = [];
        if (file_exists(Environment::getProjectPath() . '/composer.json')) {
            $json = file_get_contents(Environment::getProjectPath() . '/composer.json');
            if ($json !== false && json_validate($json)) {
                $composerManifest = json_decode($json, true);
            }
        } else {
            $io->error('No composer.json found.');
            return Command::FAILURE;
        }

        if ($composerManifest === []) {
            $io->error('Invalid composer.json.');
            return Command::FAILURE;
        }

        $phpCsFixerBinary = $composerManifest['config']['bin-dir'] ?? 'vendor/bin/php-cs-fixer';
        if (!is_file($phpCsFixerBinary)) {
            $io->error('No php-cs-fixer found: ' . $phpCsFixerBinary);
            return Command::FAILURE;
        }

        $phpCsFixerConfig = GeneralUtility::getFileAbsFileName(
            'EXT:kickstarter/Build/cgl/.php-cs-fixer.dist.php'
        );

        if (!is_file($phpCsFixerConfig)) {
            $io->error('No php-cs-fixer config found: ' . $phpCsFixerConfig);
            return Command::FAILURE;
        }

        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $cmd = sprintf(
            '%s fix %s --config=%s',
            escapeshellcmd($phpCsFixerBinary),
            implode(' ', $this->getDirectories($extensionInformation)),
            escapeshellarg($phpCsFixerConfig),
        );

        exec($cmd, $phpCsFixerOutput, $resultCode);

        $io->text($phpCsFixerOutput);

        if ($resultCode !== 0) {
            $io->error('php-cs-fixer failed.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function getDirectories(ExtensionInformation $extensionInformation): array
    {
        $directories = [];

        foreach (self::DIRECTORIES as $directory) {
            $path = $extensionInformation->getExtensionPath() . $directory;
            if (is_dir($path)) {
                $directories[] = escapeshellarg($path);
            }
        }

        return $directories;
    }

    private function isExecAvailable(): bool
    {
        $disabled = explode(',', ini_get('disable_functions') ?: '');
        $disabled = array_map(trim(...), $disabled);
        return !in_array('exec', $disabled, true);
    }
}
