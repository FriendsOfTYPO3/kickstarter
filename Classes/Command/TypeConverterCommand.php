<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/ext-kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace StefanFroemken\ExtKickstarter\Command;

use StefanFroemken\ExtKickstarter\Information\TypeConverterInformation;
use StefanFroemken\ExtKickstarter\Service\Creator\TypeConverterCreatorService;
use StefanFroemken\ExtKickstarter\Traits\AskForExtensionKeyTrait;
use StefanFroemken\ExtKickstarter\Traits\ExtensionInformationTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TypeConverterCommand extends Command
{
    use AskForExtensionKeyTrait;
    use ExtensionInformationTrait;

    public function __construct(
        private readonly TypeConverterCreatorService $typeConverterCreatorService,
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
            'We are here to assist you in creating a new TYPO3 Event Listener.',
            'Now, we will ask you a few questions to customize the event listener according to your needs.',
            'Please take your time to answer them.',
        ]);

        $this->typeConverterCreatorService->create($this->askForTypeConverterInformation($io, $input));

        return Command::SUCCESS;
    }

    private function askForTypeConverterInformation(SymfonyStyle $io, InputInterface $input): TypeConverterInformation
    {
        $extensionInformation = $this->getExtensionInformation(
            $this->askForExtensionKey($io, $input->getArgument('extension_key')),
            $io
        );

        return new TypeConverterInformation(
            $extensionInformation,
            $this->askForTypeConverterClassName($io),
        );
    }

    private function askForTypeConverterClassName(SymfonyStyle $io): string
    {
        $defaultTypeConverterClassName = null;

        do {
            $typeConverterClassName = (string)$io->ask(
                'Please provide the class name of your new Type Converter',
                $defaultTypeConverterClassName,
            );

            if (preg_match('/^[0-9]/', $typeConverterClassName)) {
                $io->error('Class name should not start with a number.');
                $defaultTypeConverterClassName = $this->tryToCorrectTypeConverterClassName($typeConverterClassName);
                $validTypeConverterClassName = false;
            } elseif (preg_match('/[^a-zA-Z0-9]/', $typeConverterClassName)) {
                $io->error('Class name contains invalid chars. Please provide just letters and numbers.');
                $defaultTypeConverterClassName = $this->tryToCorrectTypeConverterClassName($typeConverterClassName);
                $validTypeConverterClassName = false;
            } elseif (preg_match('/^[A-Z][a-zA-Z0-9]+$/', $typeConverterClassName) === 0) {
                $io->error('Action must be written in UpperCamelCase like "FileUploadTypeConverter".');
                $defaultTypeConverterClassName = $this->tryToCorrectTypeConverterClassName($typeConverterClassName);
                $validTypeConverterClassName = false;
            } elseif (!str_ends_with($typeConverterClassName, 'TypeConverter')) {
                $io->error('Class name must end with "TypeConverter".');
                $defaultTypeConverterClassName = $this->tryToCorrectTypeConverterClassName($typeConverterClassName);
                $validTypeConverterClassName = false;
            } else {
                $validTypeConverterClassName = true;
            }
        } while (!$validTypeConverterClassName);

        return $typeConverterClassName;
    }

    private function tryToCorrectTypeConverterClassName(string $givenTypeConverterClassName): string
    {
        // Remove invalid chars
        $cleanedTypeConverterClassName = preg_replace('/[^a-zA-Z0-9]/', '', $givenTypeConverterClassName);

        // Upper case first char
        $cleanedTypeConverterClassName = ucfirst($cleanedTypeConverterClassName);

        // Remove ending "tyPEconVerTEr" with wrong case
        if (str_ends_with(strtolower($cleanedTypeConverterClassName), 'typeconverter')) {
            $cleanedTypeConverterClassName = substr($cleanedTypeConverterClassName, 0, -13);
        }

        // Add "TypeConverter" with correct case
        $cleanedTypeConverterClassName .= 'TypeConverter';

        return $cleanedTypeConverterClassName;
    }
}
