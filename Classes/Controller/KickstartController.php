<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Controller;

use FriendsOfTYPO3\Kickstarter\Model\Graph;
use FriendsOfTYPO3\Kickstarter\Model\Input;
use FriendsOfTYPO3\Kickstarter\Model\Link;
use FriendsOfTYPO3\Kickstarter\Model\Output;
use FriendsOfTYPO3\Kickstarter\Service\BuildExtensionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;

/**
 * Controller to show an overview about the models
 */
readonly class KickstartController implements ControllerInterface
{
    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private BuildExtensionService $buildExtensionService
    ) {}

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        return $moduleTemplate->renderResponse('Kickstarter.html');
    }

    public function build(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->buildExtensionService->build(
                $this->buildGraphTree(\json_decode((string)$request->getBody(), true))
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function buildGraphTree(array $graph): Graph
    {
        $graphTree = new Graph();

        foreach ($graph['nodes'] as $nodeGraph) {
            $inputs = new \SplObjectStorage();
            foreach ($nodeGraph['inputs'] as $inputGraph) {
                $inputs->offsetSet(new Input($inputGraph['link'], $inputGraph['type'], $inputGraph['name']));
            }

            $outputs = new \SplObjectStorage();
            foreach ($nodeGraph['outputs'] as $outputGraph) {
                $outputs->offsetSet(new Output($outputGraph['links'], $outputGraph['type'], $outputGraph['name']));
            }

            $properties = $nodeGraph['properties'] ?? [];

            // Skip invalid nodes
            if (($nodeGraph['type'] ?? '') === '') {
                throw new \InvalidArgumentException('Node must have a type defined', 6238600738);
            }
            if ((int)($nodeGraph['id'] ?? 0) === 0) {
                throw new \InvalidArgumentException(
                    'Node must have an ID. NodeType: ' . $nodeGraph['type'],
                    1259778032
                );
            }

            $className = $this->getNodeClassName($nodeGraph['type']);
            if ($className === null) {
                throw new \InvalidArgumentException(
                    'No PHP classname for node type found: ' . $nodeGraph['type'],
                    3473484881
                );
            }

            $node = new $className(
                (int)$nodeGraph['id'],
                $nodeGraph['type'],
                $inputs,
                $outputs,
                $properties,
                $graphTree
            );

            $graphTree->addNode($node);
        }

        foreach ($graph['links'] as $link) {
            $graphTree->addLink(new Link($link[0], $link[1], $link[3], $link[5]));
        }

        return $graphTree;
    }

    private function getNodeClassName(string $nodeType): ?string
    {
        $typeNamespacePart = str_replace('/', '\\', $nodeType);
        $className = sprintf(
            '%s\\%sNode',
            '\\FriendsOfTYPO3\\Kickstarter\\Model\\Node',
            $typeNamespacePart,
        );

        return class_exists($className) ? $className : null;
    }
}
