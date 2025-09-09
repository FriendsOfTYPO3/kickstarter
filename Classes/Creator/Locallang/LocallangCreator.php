<?php

declare(strict_types=1);

namespace FriendsOfTYPO3\Kickstarter\Creator\Locallang;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\LocallangInformation;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class LocallangCreator implements LocallangCreatorInterface
{
    public function __construct(
        private FileManager $fileManager,
    ) {}

    public function create(LocallangInformation $locallangInformation): void
    {
        GeneralUtility::mkdir_deep($locallangInformation->getLanguageRessourcePath());
        $path = $locallangInformation->getFullFilePath();

        // Load existing file or start from template
        if (\is_file($path)) {
            $xml = GeneralUtility::getUrl($path);
            if ($xml === false) {
                throw new \RuntimeException(sprintf('Could not read XLIFF: %s', $path), 3548142420);
            }
            $xml = (string)\preg_replace('/^\xEF\xBB\xBF/', '', $xml);
        } else {
            $xml = $this->getTemplate($locallangInformation);
        }

        // Build DOM
        $dom = $this->loadDom($xml);
        [$fileEl, $bodyEl] = $this->ensureStructure($dom);

        // Use trans-units from LocallangInformation
        foreach ($locallangInformation->getTransUnits() as $tu) {
            // $tu is FriendsOfTYPO3\Kickstarter\Information\TransUnitInformation
            $this->upsertTransUnit(
                $dom,
                $bodyEl,
                $tu->getId(),
                $tu->getSource(),
                $tu->getTarget(),   // null means "leave target as-is" for existing TUs
                false               // keep your current non-force behavior
            );
        }

        // Serialize + write back
        $xmlOut = $dom->saveXML();
        if ($xmlOut === false) {
            throw new \RuntimeException('Failed to serialize XLIFF DOM.', 5696154828);
        }

        $xmlOut = $this->indentWithTabs($xmlOut, 2);
        GeneralUtility::writeFile($path, $xmlOut);
        $this->fileManager->createOrModifyFile($path, $xmlOut, $locallangInformation->getCreatorInformation());
    }

    private function formatDate(\DateTimeInterface $dt): string
    {
        return (clone $dt)->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Replace leading spaces at the start of each line with tabs.
     * $spacesPerTab controls how many spaces collapse into one tab (libxml uses 2).
     */
    private function indentWithTabs(string $xml, int $spacesPerTab = 2): string
    {
        if ($spacesPerTab < 1) {
            return $xml;
        }

        return (string)preg_replace_callback('/^ +/m', static function (array $matches) use ($spacesPerTab): string {
            $spaces = strlen($matches[0]);
            $tabs   = intdiv($spaces, $spacesPerTab);
            $rest   = $spaces % $spacesPerTab; // keep any odd leftover spaces as-is
            return str_repeat("\t", $tabs) . str_repeat(' ', $rest);
        }, $xml);
    }

    /**
     * Build a minimal, valid XLIFF 1.0 template and preserve the creation date format if provided.
     */
    private function getTemplate(LocallangInformation $locallangInformation): string
    {
        $formattedTime = $this->formatDate($locallangInformation->getCreationDate());

        $product = $locallangInformation->getExtensionInformation()->getExtensionKey();
        $original = $locallangInformation->getExtFilePath();

        return sprintf(
            '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<xliff version="1.0">
	<file source-language="en" datatype="plaintext" original="%s" date="%s" product-name="%s">
		<header/>
		<body/>
	</file>
</xliff>
',
            htmlspecialchars($original, ENT_QUOTES | ENT_XML1),
            $formattedTime,
            htmlspecialchars($product, ENT_QUOTES | ENT_XML1)
        );
    }

    private function loadDom(string $xml): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $opts = LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET;
        if (!$dom->loadXML($xml, $opts)) {
            throw new \RuntimeException('Invalid XLIFF XML.', 1403748209);
        }
        return $dom;
    }

    /**
     * Ensure <xliff><file><header/><body> exists and return [file, body].
     * Keeps existing nodes if present.
     */
    private function ensureStructure(\DOMDocument $dom): array
    {
        $xliff = $dom->getElementsByTagName('xliff')->item(0);
        if (!$xliff instanceof \DOMElement) {
            $xliff = $dom->createElement('xliff');
            $xliff->setAttribute('version', '1.0');
            $dom->appendChild($xliff);
        }

        $file = $xliff->getElementsByTagName('file')->item(0);
        if (!$file instanceof \DOMElement) {
            $file = $dom->createElement('file');
            $file->setAttribute('source-language', 'en');
            $file->setAttribute('datatype', 'plaintext');
            $file->setAttribute('original', 'messages');
            $xliff->appendChild($file);
        }

        $header = $file->getElementsByTagName('header')->item(0);
        if (!$header instanceof \DOMElement) {
            $header = $dom->createElement('header');
            $file->appendChild($header);
        }

        $body = $file->getElementsByTagName('body')->item(0);
        if (!$body instanceof \DOMElement) {
            $body = $dom->createElement('body');
            $file->appendChild($body);
        }

        return [$file, $body];
    }

    /**
     * Create or update a <trans-unit id="..."> with <source> and optional <target>.
     * If $force is false and an existing value would be changed, throws.
     */
    private function upsertTransUnit(
        \DOMDocument $dom,
        \DOMElement $body,
        string $id,
        string $source,
        ?string $target = null,
        bool $force = false
    ): void {
        $xp = new \DOMXPath($dom);
        $nodeList = $xp->query(sprintf('.//trans-unit[@id=%s]', $this->xpathEscape($id)), $body);
        /** @var \DOMElement|null $tu */
        $tu = $nodeList && $nodeList->length ? $nodeList->item(0) : null;

        if (!$tu instanceof \DOMElement) {
            // Create new trans-unit
            $tu = $dom->createElement('trans-unit');
            $tu->setAttribute('id', $id);
            $body->appendChild($tu);

            $src = $dom->createElement('source');
            $src->nodeValue = $source;
            $tu->appendChild($src);

            if ($target !== null) {
                $tgt = $dom->createElement('target');
                $tgt->nodeValue = $target;
                $tu->appendChild($tgt);
            }
            return;
        }

        // Exists: compare and maybe update <source>
        $src = $tu->getElementsByTagName('source')->item(0);
        if (!$src instanceof \DOMElement) {
            // Missing source in existing TU -> treat as creating it
            $src = $dom->createElement('source');
            $tu->appendChild($src);
            $src->nodeValue = $source;
        } else {
            $existingSource = $this->normalizeForCompare($src->textContent ?? '');
            $incomingSource = $this->normalizeForCompare($source);

            if ($existingSource !== $incomingSource && !$force) {
                throw new \RuntimeException(sprintf(
                    'trans-unit "%s" already exists with a different <source>. Use $force=true to overwrite.',
                    $id
                ), 9435398299);
            }
            // Set anyway (idempotent if same)
            $src->nodeValue = $source;
        }

        // Handle <target> only if provided; null means "leave as-is"
        if ($target !== null) {
            $tgt = $tu->getElementsByTagName('target')->item(0);
            if ($tgt instanceof \DOMElement) {
                $existingTarget = $this->normalizeForCompare($tgt->textContent ?? '');
                $incomingTarget = $this->normalizeForCompare($target);

                if ($existingTarget !== $incomingTarget && !$force) {
                    throw new \RuntimeException(sprintf(
                        'trans-unit "%s" already exists with a different <target>. Use $force=true to overwrite.',
                        $id
                    ), 8736663261);
                }
                $tgt->nodeValue = $target;
            } else {
                // No existing target -> create it
                $tgt = $dom->createElement('target');
                $tgt->nodeValue = $target;
                $tu->appendChild($tgt);
            }
        }
    }

    /**
     * Normalize text for comparison: trim and collapse whitespace.
     */
    private function normalizeForCompare(string $text): string
    {
        // Normalize newlines and collapse consecutive whitespace
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /**
     * Escape a literal for use in an XPath predicate.
     */
    private function xpathEscape(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }
        // concat('a', "'", 'b')
        $parts = preg_split("/(')/", $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pieces = [];
        foreach ($parts as $part) {
            if ($part === "'") {
                $pieces[] = "\"'\"";
            } elseif ($part !== '') {
                $pieces[] = "'" . $part . "'";
            }
        }
        return 'concat(' . implode(',', $pieces) . ')';
    }
}
