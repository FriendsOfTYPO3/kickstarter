<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Creator\EventListener;

use FriendsOfTYPO3\Kickstarter\Creator\FileManager;
use FriendsOfTYPO3\Kickstarter\Information\EventListenerInformation;
use FriendsOfTYPO3\Kickstarter\PhpParser\NodeFactory;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\ClassStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\DeclareStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\FileStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\MethodStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\NamespaceStructure;
use FriendsOfTYPO3\Kickstarter\PhpParser\Structure\UseStructure;
use FriendsOfTYPO3\Kickstarter\Traits\FileStructureBuilderTrait;
use PhpParser\BuilderFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class EventListenerCreator implements EventListenerCreatorInterface
{
    use FileStructureBuilderTrait;

    private BuilderFactory $builderFactory;

    public function __construct(
        private readonly NodeFactory $nodeFactory,
        private readonly FileManager $fileManager,
    ) {
        $this->builderFactory = new BuilderFactory();
    }

    public function create(EventListenerInformation $eventListenerInformation): void
    {
        GeneralUtility::mkdir_deep($eventListenerInformation->getEventListenerPath());

        $eventListenerFilePath = $eventListenerInformation->getEventListenerFilePath();
        $fileStructure = $this->buildFileStructure($eventListenerFilePath);

        if (is_file($eventListenerFilePath)) {
            $eventListenerInformation->getCreatorInformation()->fileExists(
                $eventListenerFilePath,
                sprintf(
                    'EventListeners can only be created, not modified. The file %s already exists and cannot be overridden. ',
                    $eventListenerInformation->getEventListenerFilename()
                )
            );
            return;
        }
        $this->addClassNodes($fileStructure, $eventListenerInformation);

        $this->fileManager->createFile($eventListenerFilePath, $fileStructure->getFileContents(), $eventListenerInformation->getCreatorInformation());
    }

    private function addClassNodes(FileStructure $fileStructure, EventListenerInformation $eventListenerInformation): void
    {
        $fileStructure->addDeclareStructure(
            new DeclareStructure($this->nodeFactory->createDeclareStrictTypes())
        );

        $fileStructure->addNamespaceStructure(
            new NamespaceStructure($this->nodeFactory->createNamespace(
                $eventListenerInformation->getNamespace(),
                $eventListenerInformation->getExtensionInformation(),
            ))
        );

        $fileStructure->addUseStructure(new UseStructure(
            $this->builderFactory->use('TYPO3\CMS\Core\Attribute\AsEventListener')->getNode()
        ));

        $fileStructure->addClassStructure(
            new ClassStructure(
                $this->builderFactory
                    ->class($eventListenerInformation->getEventListenerClassName())
                    ->makeFinal()
                    ->getNode(),
            )
        );

        $fileStructure->addMethodStructure(
            new MethodStructure(
                $this->builderFactory
                    ->method('__invoke')
                    ->addParam($this->builderFactory->param('event')->setType('Replace\Me\Event'))
                    ->makePublic()
                    ->setReturnType('void')
                    ->addAttribute($this->builderFactory->attribute(
                        'AsEventListener',
                        [
                            'identifier' => $eventListenerInformation->getEventListenerIdentifier(),
                        ]
                    ))
                    ->getNode()
            )
        );
    }
}
