<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\SitePackage;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\SitePackageInformation;

/**
 * Creates the labels.xlf of the site set, which carries the label of the set itself and the labels
 * the page layouts reference. It resolves to the translation domain returned by
 * SitePackageInformation::getTranslationDomain(), which follows from the path of this file and is
 * registered by nothing.
 */
class LabelsCreator implements SitePackageCreatorInterface
{
    private const FILENAME = 'labels.xlf';

    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(SitePackageInformation $sitePackageInformation): void
    {
        $this->fileManager->createOrModifyFile(
            $sitePackageInformation->getSiteSetPath() . self::FILENAME,
            $this->getFileContent($sitePackageInformation),
            $sitePackageInformation->getCreatorInformation()
        );
    }

    private function getFileContent(SitePackageInformation $sitePackageInformation): string
    {
        $extensionInformation = $sitePackageInformation->getExtensionInformation();
        $siteSetInformation = $sitePackageInformation->getSiteSetInformation();

        $transUnits = [
            'label' => $siteSetInformation->getLabel(),
            'backend_layout.default.title' => 'Default',
            'backend_layout.default.main.name' => 'Main',
        ];

        $original = sprintf(
            'EXT:%s/%s%s/%s',
            $extensionInformation->getExtensionKey(),
            $extensionInformation::SITE_SET_PATH,
            $siteSetInformation->getPath(),
            self::FILENAME
        );

        return sprintf(
            $this->getTemplate(),
            $this->escape($original),
            $this->escape($extensionInformation->getExtensionKey()),
            $this->buildTransUnits($transUnits)
        );
    }

    /**
     * @param array<string, string> $transUnits
     */
    private function buildTransUnits(array $transUnits): string
    {
        $lines = [];
        foreach ($transUnits as $id => $source) {
            $lines[] = sprintf("\t\t\t<trans-unit id=\"%s\">", $this->escape($id));
            $lines[] = sprintf("\t\t\t\t<source>%s</source>", $this->escape($source));
            $lines[] = "\t\t\t</trans-unit>";
        }

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
    }

    private function getTemplate(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
	<file source-language="en" datatype="plaintext" original="%1$s" product-name="%2$s">
		<header/>
		<body>
%3$s
		</body>
	</file>
</xliff>

XML;
    }
}
