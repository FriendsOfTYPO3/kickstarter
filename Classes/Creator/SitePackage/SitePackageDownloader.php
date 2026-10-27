<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\SitePackage;

use FriendsOfTYPO3\Kickstarter\Configuration\ExtConf;
use FriendsOfTYPO3\Kickstarter\Information\DownloadedSitePackageInformation;
use FriendsOfTYPO3\Kickstarter\Information\ExtensionInformation;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageDownloadInformation;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetches a site package from the builder at get.typo3.org and unpacks it into the export
 * directory. The builder names the extension itself, so the extension key, the Composer package
 * name and the site set are read back from what it delivered.
 *
 * This is deliberately not a SitePackageCreatorInterface: those write the files of a locally
 * generated site package, while this one replaces all of them at once.
 */
readonly class SitePackageDownloader
{
    private const API_URL = 'https://get.typo3.org/api/v1/sitepackage/';

    /**
     * The skeletons the builder offers, newest answer first. Asking for any other value is
     * answered with HTTP 400.
     */
    public const BASE_PACKAGES = [
        'fluid_styled_content',
        'bootstrap_package',
    ];

    /**
     * The TYPO3 versions the builder builds for. It rejects every other value, including a patch
     * level and including a maintained version it has not been taught yet, so the command maps the
     * running TYPO3 version onto this list instead of sending it as it is.
     */
    public const TYPO3_VERSIONS = [
        '12.4',
        '13.4',
        '14.3',
    ];

    public function __construct(
        private RequestFactory $requestFactory,
        private ExtConf $extConf,
    ) {}

    /**
     * Returns null when the builder refused the request or the target directory is occupied. The
     * reason is recorded in the CreatorInformation of the given information object.
     */
    public function download(SitePackageDownloadInformation $downloadInformation): ?DownloadedSitePackageInformation
    {
        $creatorInformation = $downloadInformation->getCreatorInformation();

        try {
            $response = $this->requestFactory->request(self::API_URL, 'POST', [
                'json' => $this->buildPayload($downloadInformation),
                'headers' => ['Accept' => 'application/zip'],
                'http_errors' => false,
            ]);
        } catch (\Throwable $exception) {
            $creatorInformation->writingFileFailed(
                self::API_URL,
                'The site package builder could not be reached: ' . $exception->getMessage()
            );
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $creatorInformation->writingFileFailed(
                self::API_URL,
                sprintf(
                    'The site package builder answered with HTTP %d: %s',
                    $response->getStatusCode(),
                    $this->describeError((string)$response->getBody())
                )
            );
            return null;
        }

        $extensionKey = $this->detectExtensionKey($response->getHeaderLine('content-disposition'));
        if ($extensionKey === '') {
            $creatorInformation->writingFileFailed(
                self::API_URL,
                'The site package builder delivered no file name, so the extension key is unknown.'
            );
            return null;
        }

        $extensionPath = rtrim($this->extConf->getExportDirectory(), '/') . '/' . $extensionKey . '/';
        if (is_dir($extensionPath) && !$this->isEmptyDirectory($extensionPath)) {
            $creatorInformation->fileExists(
                $extensionPath,
                sprintf(
                    'The site package builder named the extension "%s" and a directory of that name is not empty.',
                    $extensionKey
                )
            );
            return null;
        }

        return $this->extract($downloadInformation, (string)$response->getBody(), $extensionKey, $extensionPath);
    }

    private function extract(
        SitePackageDownloadInformation $downloadInformation,
        string $zipData,
        string $extensionKey,
        string $extensionPath
    ): ?DownloadedSitePackageInformation {
        $creatorInformation = $downloadInformation->getCreatorInformation();

        $temporaryZip = GeneralUtility::tempnam('sitepackage_', '.zip');
        if (file_put_contents($temporaryZip, $zipData) === false) {
            $creatorInformation->writingFileFailed($temporaryZip, 'The delivered ZIP archive could not be stored.');
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($temporaryZip) !== true) {
            unlink($temporaryZip);
            $creatorInformation->writingFileFailed(
                $extensionPath,
                'The site package builder delivered something that is not a ZIP archive.'
            );
            return null;
        }

        GeneralUtility::mkdir_deep($extensionPath);

        if (!$zip->extractTo($extensionPath)) {
            $zip->close();
            unlink($temporaryZip);
            $creatorInformation->writingFileFailed($extensionPath, 'The delivered ZIP archive could not be unpacked.');
            return null;
        }

        // Report every unpacked file, so the file report of the command covers a downloaded site
        // package the same way it covers a generated one.
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if ($entry === false || str_ends_with($entry, '/')) {
                continue;
            }
            $creatorInformation->fileAdded($extensionPath . $entry);
        }

        $zip->close();
        unlink($temporaryZip);

        [$siteSetIdentifier, $siteSetPath] = $this->detectSiteSet($extensionPath);

        return new DownloadedSitePackageInformation(
            $extensionKey,
            $this->detectComposerPackageName($extensionPath, $extensionKey),
            $extensionPath,
            $siteSetIdentifier,
            $siteSetPath,
        );
    }

    /**
     * @return array{base_package: string, typo3_version: float, title: string, description: string, repository_url: string, author: array{name: string, email: string, company: string, homepage: string}}
     */
    private function buildPayload(SitePackageDownloadInformation $downloadInformation): array
    {
        return [
            'base_package' => $downloadInformation->getBasePackage(),
            // The builder validates this against a list of versions, not as a number, so the value
            // has to be the major and minor version of a TYPO3 version it knows.
            'typo3_version' => (float)$downloadInformation->getTypo3Version(),
            'title' => $downloadInformation->getTitle(),
            'description' => $downloadInformation->getDescription(),
            'repository_url' => $downloadInformation->getRepositoryUrl(),
            'author' => [
                'name' => $downloadInformation->getAuthor(),
                'email' => $downloadInformation->getAuthorEmail(),
                'company' => $downloadInformation->getAuthorCompany(),
                'homepage' => $downloadInformation->getHomepage(),
            ],
        ];
    }

    /**
     * The builder answers with a file name such as "my_site_package_1.0.0.zip", which carries the
     * extension key it chose.
     */
    private function detectExtensionKey(string $contentDisposition): string
    {
        if (!preg_match('/filename="?([^";]+)\.zip"?/i', $contentDisposition, $matches)) {
            return '';
        }

        $name = basename($matches[1]);

        return (string)preg_replace('/_\d+\.\d+\.\d+$/', '', $name);
    }

    private function detectComposerPackageName(string $extensionPath, string $extensionKey): string
    {
        $composerJson = $extensionPath . 'composer.json';
        if (!is_file($composerJson)) {
            return $extensionKey;
        }

        $composerData = json_decode((string)file_get_contents($composerJson), true);

        return is_array($composerData) && is_string($composerData['name'] ?? null)
            ? $composerData['name']
            : $extensionKey;
    }

    /**
     * Reads the identifier of the site set the downloaded package ships. The builder decides how
     * that set is named and where it sits, so both are taken from the delivered files.
     *
     * @return array{0: string, 1: string}
     */
    private function detectSiteSet(string $extensionPath): array
    {
        $setsPath = $extensionPath . ExtensionInformation::SITE_SET_PATH;

        foreach ((array)glob($setsPath . '*/config.yaml') as $configFile) {
            if (!is_string($configFile)) {
                continue;
            }

            $config = Yaml::parseFile($configFile);
            if (!is_array($config) || !is_string($config['name'] ?? null)) {
                continue;
            }

            return [$config['name'], basename(dirname($configFile))];
        }

        return ['', ''];
    }

    private function isEmptyDirectory(string $path): bool
    {
        $entries = scandir($path);

        return is_array($entries) && array_diff($entries, ['.', '..']) === [];
    }

    /**
     * The builder answers a rejected request with a JSON body naming every field it refused.
     */
    private function describeError(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '[empty response]';
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !is_array($decoded['errors'] ?? null)) {
            return $body;
        }

        return $this->flattenErrors($decoded['errors']);
    }

    /**
     * @param array<mixed> $errors
     */
    private function flattenErrors(array $errors, string $prefix = ''): string
    {
        $messages = [];

        foreach ($errors as $field => $error) {
            $name = $prefix === '' ? (string)$field : $prefix . '.' . $field;

            if (is_array($error) && array_is_list($error)) {
                $messages[] = $name . ': ' . implode(' ', array_map(static fn(mixed $value): string => (string)$value, $error));
                continue;
            }

            if (is_array($error)) {
                $messages[] = $this->flattenErrors($error, $name);
                continue;
            }

            $messages[] = $name . ': ' . $error;
        }

        return implode('; ', $messages);
    }
}
