<?php

declare(strict_types=1);

/*
 * This file is part of the package friendsoftypo3/kickstarter.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FriendsOfTYPO3\Kickstarter\Tests\Functional\Fixtures;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Answers every request with a canned response, or with the given exception, and records what it
 * was asked, so a test can check the request without reaching the network. RequestFactory is
 * readonly, which is why this is a subclass rather than a mock: the recorded requests live in a
 * mutable ArrayObject.
 */
final readonly class FakeRequestFactory extends RequestFactory
{
    /**
     * @param \ArrayObject<int, array{uri: string, method: string, options: array<string, mixed>}> $recordedRequests
     */
    public function __construct(
        private ResponseInterface $response,
        private \ArrayObject $recordedRequests,
        private ?\Throwable $exception = null,
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function request(
        string $uri,
        string $method = 'GET',
        array $options = [],
        ?string $context = null
    ): ResponseInterface {
        $this->recordedRequests->append([
            'uri' => $uri,
            'method' => $method,
            'options' => $options,
        ]);

        if ($this->exception instanceof \Throwable) {
            throw $this->exception;
        }

        return $this->response;
    }
}
