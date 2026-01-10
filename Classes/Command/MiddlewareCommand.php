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
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\MiddlewareClassNameQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\Question\MiddlewareIdentifierQuestion;
use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use FriendsOfTYPO3\Kickstarter\Information\MiddleWareInformation;
use FriendsOfTYPO3\Kickstarter\Service\Creator\MiddlewareCreatorService;
use FriendsOfTYPO3\Kickstarter\Traits\AskForExtensionKeyTrait;
use FriendsOfTYPO3\Kickstarter\Traits\CreatorInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\ExtensionInformationTrait;
use FriendsOfTYPO3\Kickstarter\Traits\TryToCorrectClassNameTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;

#[AsCommand('make:middleware', 'Add a middleware handler to your TYPO3 extension.')]
#[AsNonSchedulableCommand]
class MiddlewareCommand extends Command
{
    use AskForExtensionKeyTrait;
    use CreatorInformationTrait;
    use ExtensionInformationTrait;
    use TryToCorrectClassNameTrait;

    public function __construct(
        private readonly MiddlewareCreatorService $middlewareCreatorService,
        private readonly MiddlewareStackResolver $middlewareStackResolver,
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
            'We are here to assist you in creating a new TYPO3 Middleware.',
            'Now, we will ask you a few questions to customize the middleware according to your needs.',
            'Please take your time to answer them.',
        ]);

        $middlewareInformation = $this->askForMiddlewareInformation($commandContext);
        $this->middlewareCreatorService->create($middlewareInformation);
        $this->printCreatorInformation($middlewareInformation->getCreatorInformation(), $commandContext);

        return Command::SUCCESS;
    }

    private function askForMiddlewareInformation(CommandContext $commandContext): MiddlewareInformation
    {
        $io = $commandContext->getIo();
        $extensionInformation = $this->getExtensionInformation(
            (string)$this->questionCollection->askQuestion(
                ChooseExtensionKeyQuestion::ARGUMENT_NAME,
                $commandContext,
            ),
            $commandContext
        );

        $className = (string)$this->questionCollection->askQuestion(
            MiddlewareClassNameQuestion::ARGUMENT_NAME,
            $commandContext
        );
        $stack = $this->askForMiddlewareStack($commandContext);
        $prefix = str_replace('_', '', $extensionInformation->getExtensionKey());
        $middlewareIdentifier = $this->askForMiddlewareIdentifier(
            $commandContext,
            $prefix . '/' . strtolower(preg_replace('/Middleware$/i', '', $className)),
            $stack
        );

        return new MiddlewareInformation(
            $extensionInformation,
            $className,
            $stack,
            $middlewareIdentifier,
            $this->askForBeforeAfter($io, $stack, 'before'),
            $this->askForBeforeAfter($io, $stack, 'after'),
        );
    }

    private function askForMiddlewareIdentifier(CommandContext $commandContext, string $default, string $stack): string
    {
        do {
            $valid = false;
            $identifier = (string)$this->questionCollection->askQuestion(
                MiddlewareIdentifierQuestion::ARGUMENT_NAME,
                $commandContext,
                $default
            );

            if (in_array($identifier, $this->middlewareStackResolver->resolve($stack), true)) {
                $commandContext->getIo()->warning(sprintf('The identifier "%s" already exists in the configuration!', $identifier));
                $default = $identifier;
                continue;
            }
            $valid = true;

        } while (!$valid);

        return $identifier;
    }

    private function askForMiddlewareStack(CommandContext $commandContext): string
    {
        return $commandContext->getIo()->choice(
            'Is this middleware stack for the frontend or backend?',
            ['frontend', 'backend'],
            'frontend',
        );
    }

    /**
     * @return mixed[]
     */
    private function askForBeforeAfter(SymfonyStyle $io, string $stack, string $location): array
    {
        $entries = array_keys($this->middlewareStackResolver->resolve($stack) ?? []);

        array_unshift($entries, 'none');

        $answer = $io->choice(
            'Should this middleware be executed ' . $location . ' specific middleware(s)? (select "none" if not applicable)',
            $entries,
            'none',
            true
        );

        // Normalize the result
        if (in_array('none', $answer, true)) {
            return [];
        }

        return $answer;
    }
}
