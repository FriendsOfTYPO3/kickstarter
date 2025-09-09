<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\Middleware;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\MiddleWareInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ReturnStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates an icon for the plugin
 */
class RequestMiddlewaresCreator implements MiddlewareCreatorInterface
{
    use FileStructureBuilderTrait;

    public function __construct(
        private readonly FileManager $fileManager,
    ) {}

    public function create(MiddlewareInformation $middlewareInformation): void
    {
        $path = $middlewareInformation->getExtensionInformation()->getExtensionPath() . 'Configuration/';
        GeneralUtility::mkdir_deep($path);
        $filePath = $path . 'RequestMiddlewares.php';

        $fileStructure = $this->buildFileStructure($filePath);

        if (!is_file($filePath)) {
            $this->addNode($fileStructure);
        } elseif ($fileStructure->getReturnStructures()->count() === 0) {
            $middlewareInformation->getCreatorInformation()
                ->fileModificationFailed(sprintf('File %s exists but is malformed (return missing). ', $filePath));
            return;
        }
        if ($this->addStackEntry($filePath, $fileStructure, $middlewareInformation)) {
            $this->fileManager->createOrModifyFile($filePath, $fileStructure->getFileContents(), $middlewareInformation->getCreatorInformation());
        }
    }

    private function addNode(FileStructure $fileStructure): void
    {
        $fileStructure->addReturnStructure(
            new ReturnStructure(
                new Return_(
                    new Array_([])
                )
            )
        );
    }

    private function addStackEntry(string $filePath, FileStructure $fileStructure, MiddleWareInformation $middlewareInformation): bool
    {
        /** @var ReturnStructure $returnStructure */
        $returnStructure = $fileStructure->getReturnStructures()->current();

        $mainArray = $returnStructure->getNode()->expr;
        if (!$mainArray instanceof Array_) {
            $middlewareInformation->getCreatorInformation()
                ->fileModificationFailed(sprintf('File %s exists but malformed (does not return an array). ', $filePath));
            return false;
        }

        $stackArray = $this->getExistingStack($mainArray, $middlewareInformation->getStack());

        if (!$stackArray instanceof Expr) {
            $stackArray = new Array_([]);
            $mainArray->items[] =  new ArrayItem($stackArray, new String_($middlewareInformation->getStack()));
        } elseif (!$stackArray instanceof Array_) {
            $middlewareInformation->getCreatorInformation()
                ->fileModificationFailed(sprintf('File %s exists but is malformed (key %s does not contain an array). ', $middlewareInformation->getStack(), $filePath));
            return false;
        }
        if ($this->hasIdentifier($stackArray, $middlewareInformation->getIdentifier())) {
            $middlewareInformation->getCreatorInformation()
                ->fileNotModified(sprintf('File %s exists and already contains key %s. It does not need to be modified. ', $filePath, $middlewareInformation->getIdentifier()));
            return false;
        }
        $stackArray->items[] = $this->createMiddlewareEntry($middlewareInformation);
        $this->sortArrayItemsByKey($stackArray);
        return true;
    }

    private function sortArrayItemsByKey(Array_ $arrayNode): void
    {
        usort($arrayNode->items, static fn(ArrayItem $a, ArrayItem $b): int => strcmp($a->key?->value ?? '', $b->key?->value ?? ''));
    }

    private function hasIdentifier(Array_ $stackArray, string $identifier): bool
    {
        foreach ($stackArray->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === $identifier) {
                return true;
            }
        }

        return false;
    }

    private function getExistingStack(Array_ $stackArray, string $stack): ?Expr
    {
        foreach ($stackArray->items as $item) {
            if ($item->key instanceof String_ && $item->key->value === $stack) {
                return $item->value;
            }
        }

        return null;
    }

    private function createMiddlewareEntry(MiddleWareInformation $middlewareInformation): ArrayItem
    {
        $beforeArray = $this->getBeforeAfterArray($middlewareInformation->getBefore());
        $afterArray = $this->getBeforeAfterArray($middlewareInformation->getAfter());

        return new ArrayItem(
            new Array_([
                new ArrayItem(
                    new ClassConstFetch(
                        new FullyQualified(trim($middlewareInformation->getNamespace() . '\\' . $middlewareInformation->getClassName(), '\\')),
                        'class'
                    ),
                    new String_('target')
                ),
                new ArrayItem(
                    $beforeArray,
                    new String_('before')
                ),
                new ArrayItem(
                    $afterArray,
                    new String_('after')
                ),
            ]),
            new String_($middlewareInformation->getIdentifier())
        );
    }

    /**
     * @param string[] $identifiers
     */
    private function getBeforeAfterArray(array $identifiers): Array_
    {
        $items = [];

        foreach ($identifiers as $id) {
            $items[] = new ArrayItem(new String_($id));
        }

        return new Array_($items);
    }
}
