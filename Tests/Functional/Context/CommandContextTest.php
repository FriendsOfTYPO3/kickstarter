<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Context;

use FriendsOfTYPO3\Kickstarter\Context\CommandContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class CommandContextTest extends FunctionalTestCase
{
    protected CommandContext $subject;

    protected InputInterface|MockObject $inputMock;

    protected OutputInterface|MockObject $outputMock;

    protected array $coreExtensionsToLoad = [
        'extensionmanager',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    protected function setUp(): void
    {
        $this->inputMock = $this->createMock(InputInterface::class);
        $this->outputMock = $this->createMock(OutputInterface::class);

        $this->subject = new CommandContext(
            $this->inputMock,
            $this->outputMock,
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->inputMock,
            $this->outputMock,
            $this->subject,
        );
    }

    #[Test]
    public function getIoWillReturnObjectOfTypeSymfonyStyle(): void
    {
        self::assertInstanceOf(
            SymfonyStyle::class,
            $this->subject->getIo(),
        );
    }

    #[Test]
    public function getInputWillReturnObjectOfTypeInputInterface(): void
    {
        self::assertInstanceOf(
            InputInterface::class,
            $this->subject->getInput(),
        );

        self::assertSame(
            $this->inputMock,
            $this->subject->getInput(),
        );
    }

    #[Test]
    public function getOutputWillReturnObjectOfTypeOutputInterface(): void
    {
        self::assertInstanceOf(
            OutputInterface::class,
            $this->subject->getOutput(),
        );

        self::assertSame(
            $this->outputMock,
            $this->subject->getOutput(),
        );
    }
}
