<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Model\Node\Tca;

use FriendsOfTYPO3\Kickstarter\Model\AbstractNode;
use FriendsOfTYPO3\Kickstarter\Model\Node\Tca\Type\InputNode;

class TableNode extends AbstractNode
{
    public function getTableName(): string
    {
        return $this->getProperties()['tableName'] ?? '';
    }

    public function getTableFilename(): string
    {
        return $this->getTableName() . '.php';
    }

    public function getTitle(): string
    {
        return $this->getProperties()['title'] ?? '';
    }

    public function getLabel(): string
    {
        $tableLabel = $this->getProperties()['label'] ?? '';

        // Check columns, if one is defined as useAsTableTitle
        if ($tableLabel === '') {
            /** @var \SplObjectStorage|InputNode[] $inputNodes */
            $inputNodes = $this->graph->getLinkedOutputNodesByName(
                $this,
                'tcaColumns',
                'Tca/Type/Input'
            );

            foreach ($inputNodes as $inputNode) {
                if ($inputNode->useAsTableLabel()) {
                    $tableLabel = $inputNode->getColumnName();
                    break;
                }
            }
        }

        return $tableLabel;
    }

    /**
     * @return \SplObjectStorage|AbstractColumnNode[]
     */
    public function getColumnNodes(): \SplObjectStorage
    {
        return $this->graph->getLinkedOutputNodesByName($this, 'tcaColumns');
    }

    /**
     * @return \SplObjectStorage|AbstractColumnNode[]
     */
    public function getModelProperties(): \SplObjectStorage
    {
        $modelProperties = new \SplObjectStorage();

        foreach ($this->getColumnNodes() as $columnNode) {
            if ($columnNode->isModelProperty()) {
                $modelProperties->offsetSet($columnNode);
            }
        }

        return $modelProperties;
    }
}
