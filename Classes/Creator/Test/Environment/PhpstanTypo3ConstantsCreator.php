<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Test\Environment;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\TestEnvInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpstanTypo3ConstantsCreator implements TestEnvCreatorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(TestEnvInformation $testEnvInformation): void
    {
        $phpstanPath = $testEnvInformation->getBuildPath() . 'phpstan/';
        GeneralUtility::mkdir_deep($phpstanPath);
        $targetFile = $phpstanPath . 'phpstan-typo3-constants.php';
        if (is_file($targetFile)) {
            $testEnvInformation->getCreatorInformation()->fileExists(
                $targetFile
            );
            return;
        }
        $this->fileManager->createFile(
            $targetFile,
            $this->getTemplate(),
            $testEnvInformation->getCreatorInformation(),
        );
    }

    private function getTemplate(): string
    {
        return <<<'EOT'
<?php

defined('LF') ?: define('LF', chr(10));
defined('CR') ?: define('CR', chr(13));
defined('CRLF') ?: define('CRLF', CR . LF);
EOT;
    }
}
