<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Command;

use FriendsOfTYPO3\Kickstarter\Command\Input\QuestionCollection;
use FriendsOfTYPO3\Kickstarter\Command\SitePackageCommand;
use FriendsOfTYPO3\Kickstarter\Service\Creator\SitePackageCreatorService;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SitePackageCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'frontend',
        'backend',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    #[Test]
    public function executeWarnsThatSitePackageIsNotSupportedForTypo3V14(): void
    {
        $creatorMock = $this->createMock(SitePackageCreatorService::class);
        $creatorMock->expects(self::never())->method('create');

        $realCommand = $this->get(SitePackageCommand::class);
        $property = new \ReflectionProperty(SitePackageCommand::class, 'questionCollection');
        /** @var QuestionCollection $questionCollection */
        $questionCollection = $property->getValue($realCommand);

        $command = new SitePackageCommand($creatorMock, $questionCollection);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);

        $display = (string)preg_replace('/\s+/', ' ', $commandTester->getDisplay());
        self::assertStringContainsString('Creating a site package for TYPO3 v14 is not yet supported.', $display);
    }
}
