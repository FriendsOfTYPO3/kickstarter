<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Creator\SitePackage;

use TYPO3\CMS\Core\Http\Stream;
use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Creator\SitePackage\SitePackageDownloader;
use FriendsOfTYPO3\Kickstarter\Information\CreatorInformation;
use FriendsOfTYPO3\Kickstarter\Information\DownloadedSitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\FileModificationInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageDownloadInformation;
use FriendsOfTYPO3\Kickstarter\Tests\Functional\Fixtures\FakeRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SitePackageDownloaderTest extends FunctionalTestCase
{
    private const EXTENSION_KEY = 'my_site_package';

    protected array $coreExtensionsToLoad = [
        'frontend',
        'backend',
    ];

    protected array $testExtensionsToLoad = [
        'friendsoftypo3/kickstarter',
    ];

    private string $extensionDir = '';

    /**
     * @var \ArrayObject<int, array{uri: string, method: string, options: array<string, mixed>}>
     */
    private \ArrayObject $recordedRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recordedRequests = new \ArrayObject();
        $this->extensionDir = rtrim($this->get(ExtConf::class)->getExportDirectory(), '/')
            . '/' . self::EXTENSION_KEY . '/';
        $this->removeExtensionDir();
    }

    protected function tearDown(): void
    {
        $this->removeExtensionDir();

        parent::tearDown();
    }

    #[Test]
    public function downloadUnpacksTheDeliveredArchiveAndReadsBackWhatTheBuilderNamed(): void
    {
        $creatorInformation = new CreatorInformation();

        $downloaded = $this->createDownloader($this->createZipResponse())->download(
            $this->createDownloadInformation($creatorInformation)
        );

        self::assertInstanceOf(DownloadedSitePackageInformation::class, $downloaded);
        self::assertSame(self::EXTENSION_KEY, $downloaded->getExtensionKey());
        self::assertSame('example-company/my-site-package', $downloaded->getComposerPackageName());
        self::assertSame($this->extensionDir, $downloaded->getExtensionPath());

        // Identifier and directory of the site set are the builder's choice, so both are read back
        // from the delivered files rather than derived from the answers.
        self::assertSame('example-company/my-site-package', $downloaded->getSiteSetIdentifier());
        self::assertSame('SitePackage', $downloaded->getSiteSetPath());

        self::assertFileExists($this->extensionDir . 'composer.json');
        self::assertFileExists($this->extensionDir . 'Configuration/Sets/SitePackage/config.yaml');
        self::assertFileExists($this->extensionDir . 'Resources/Private/PageView/Pages/Default.html');
    }

    #[Test]
    public function downloadReportsEveryUnpackedFileAndNoDirectory(): void
    {
        $creatorInformation = new CreatorInformation();

        $this->createDownloader($this->createZipResponse())->download(
            $this->createDownloadInformation($creatorInformation)
        );

        $reportedPaths = $this->reportedPaths($creatorInformation);

        self::assertContains($this->extensionDir . 'composer.json', $reportedPaths);
        self::assertContains($this->extensionDir . 'Configuration/Sets/SitePackage/config.yaml', $reportedPaths);
        self::assertContains($this->extensionDir . 'Resources/Private/PageView/Pages/Default.html', $reportedPaths);

        foreach ($reportedPaths as $reportedPath) {
            self::assertStringEndsNotWith(
                '/',
                $reportedPath,
                'A directory of the archive was reported as a created file.'
            );
        }
    }

    #[Test]
    public function downloadSendsTheVersionAsANumberTheBuilderAccepts(): void
    {
        $this->createDownloader($this->createZipResponse())->download(
            $this->createDownloadInformation(new CreatorInformation())
        );

        self::assertCount(1, $this->recordedRequests);
        $request = $this->recordedRequests[0];

        self::assertSame('https://get.typo3.org/api/v1/sitepackage/', $request['uri']);
        self::assertSame('POST', $request['method']);

        $payload = $request['options']['json'];
        self::assertIsArray($payload);

        // The builder validates this against a list of versions of its own. Sending the version of
        // the site package instead, as this command once did, asks it for TYPO3 0.0.1.
        self::assertSame(14.3, $payload['typo3_version']);
        self::assertSame('fluid_styled_content', $payload['base_package']);
        self::assertSame('My Site Package', $payload['title']);
        self::assertSame([
            'name' => 'Lina Wolf',
            'email' => 'author@example.org',
            'company' => 'Example Company',
            'homepage' => 'https://example.org',
        ], $payload['author']);
    }

    #[Test]
    public function downloadReportsTheFieldsTheBuilderRefused(): void
    {
        $body = '{"errors":{"typo3_version":["The selected choice is invalid."],'
            . '"author":{"company":["This value is too short. It should have 3 characters or more."]}}}';
        $creatorInformation = new CreatorInformation();

        $downloaded = $this->createDownloader(new Response($this->createStream($body), 400))->download(
            $this->createDownloadInformation($creatorInformation)
        );

        self::assertNull($downloaded);
        self::assertDirectoryDoesNotExist($this->extensionDir);

        $messages = $this->reportedMessages($creatorInformation);
        self::assertStringContainsString('HTTP 400', $messages);
        self::assertStringContainsString('typo3_version: The selected choice is invalid.', $messages);
        self::assertStringContainsString('author.company: This value is too short.', $messages);
    }

    #[Test]
    public function downloadRefusesToUnpackIntoADirectoryThatIsNotEmpty(): void
    {
        GeneralUtility::mkdir_deep($this->extensionDir);
        file_put_contents($this->extensionDir . 'composer.json', '{"name":"vendor/keep-me"}');

        $creatorInformation = new CreatorInformation();

        $downloaded = $this->createDownloader($this->createZipResponse())->download(
            $this->createDownloadInformation($creatorInformation)
        );

        self::assertNull($downloaded);
        self::assertStringEqualsFile($this->extensionDir . 'composer.json', '{"name":"vendor/keep-me"}');

        $messages = $this->reportedMessages($creatorInformation);
        self::assertStringContainsString('is not empty', $messages);
    }

    #[Test]
    public function downloadFailsWhenTheBuilderCannotBeReached(): void
    {
        $creatorInformation = new CreatorInformation();

        $downloader = new SitePackageDownloader(
            new FakeRequestFactory(
                new Response(),
                $this->recordedRequests,
                new \RuntimeException('Could not resolve host: get.typo3.org', 1761500000)
            ),
            $this->get(ExtConf::class)
        );

        self::assertNull($downloader->download($this->createDownloadInformation($creatorInformation)));

        $messages = $this->reportedMessages($creatorInformation);
        self::assertStringContainsString('could not be reached', $messages);
        self::assertStringContainsString('Could not resolve host', $messages);
    }

    private function createDownloader(ResponseInterface $response): SitePackageDownloader
    {
        return new SitePackageDownloader(
            new FakeRequestFactory($response, $this->recordedRequests),
            $this->get(ExtConf::class)
        );
    }

    private function createDownloadInformation(CreatorInformation $creatorInformation): SitePackageDownloadInformation
    {
        return new SitePackageDownloadInformation(
            basePackage: 'fluid_styled_content',
            typo3Version: '14.3',
            title: 'My Site Package',
            description: 'Site package of My Site Package.',
            author: 'Lina Wolf',
            authorEmail: 'author@example.org',
            authorCompany: 'Example Company',
            homepage: 'https://example.org',
            creatorInformation: $creatorInformation,
        );
    }

    /**
     * A response shaped the way the builder answers: the extension key sits in the file name, and
     * the archive carries the site package below no directory of its own.
     */
    private function createZipResponse(): ResponseInterface
    {
        $zipFile = GeneralUtility::tempnam('test_sitepackage_', '.zip');
        unlink($zipFile);

        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE);
        $zip->addEmptyDir('Configuration/Sets/SitePackage');
        $zip->addFromString('composer.json', '{"name":"example-company/my-site-package"}');
        $zip->addFromString(
            'Configuration/Sets/SitePackage/config.yaml',
            "name: example-company/my-site-package\nlabel: 'My Site Package'\n"
        );
        $zip->addFromString('Resources/Private/PageView/Pages/Default.html', '<f:layout name="Default" />');
        $zip->close();

        $zipData = (string)file_get_contents($zipFile);
        unlink($zipFile);

        return new Response($this->createStream($zipData), 200, [
            'content-disposition' => 'attachment; filename=' . self::EXTENSION_KEY . '_1.0.0.zip',
        ]);
    }

    private function createStream(string $content): Stream
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($content);
        $stream->rewind();

        return $stream;
    }

    /**
     * @return string[]
     */
    private function reportedPaths(CreatorInformation $creatorInformation): array
    {
        return array_map(
            static fn(FileModificationInformation $modification): string => $modification->getPath(),
            $creatorInformation->getFileModifications()
        );
    }

    private function reportedMessages(CreatorInformation $creatorInformation): string
    {
        return implode("\n", array_map(
            static fn(FileModificationInformation $modification): string => $modification->getMessage(),
            $creatorInformation->getFileModifications()
        ));
    }

    private function removeExtensionDir(): void
    {
        if ($this->extensionDir !== '' && is_dir($this->extensionDir)) {
            GeneralUtility::rmdir($this->extensionDir, true);
        }
    }
}
