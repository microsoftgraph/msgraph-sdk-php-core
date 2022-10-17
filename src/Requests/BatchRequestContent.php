<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class BatchRequestContent
 *
 * Contains multiple BatchRequestItems. Represents the entire batch request payload
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BatchRequestContent implements Parsable
{
    public const MAX_REQUESTS = 20;

    /**
     * @var array<string, BatchRequestItem> requests by request ID
     */
    private array $requests = [];

    /**
     * @param RequestInformation[]|BatchRequestItem[] $requests Converts $requests to BatchRequestItems with auto-generated incrementing IDs starting from "0".
     *                                         Use getRequests() to fetch created BatchRequestItem objects.
     * @throws \JsonException
     * @throws \League\Uri\Contracts\UriException
     */
    public function __construct(array $requests = [])
    {
        $this->setRequests(
            array_map(fn ($request) => is_a($request, BatchRequestItem::class) ? $request : new BatchRequestItem($request),
        $requests));
    }

    /**
     * @return array<BatchRequestItem>
     */
    public function getRequests(): array
    {
        return array_values($this->requests);
    }

    /**
     * @param BatchRequestItem[] $requests
     */
    public function setRequests(array $requests): void
    {
        if (count($requests) >= self::MAX_REQUESTS) {
            throw new \InvalidArgumentException("Maximum number of requests is ".self::MAX_REQUESTS);
        }
        array_map(fn ($request) => $this->addRequest($request), $requests);
    }

    /**
     * @param BatchRequestItem $request Assigns unique request Id if none is provided
     */
    public function addRequest(BatchRequestItem $request): void
    {
        if (count($this->requests) >= self::MAX_REQUESTS) {
            throw new \RuntimeException("Maximum number of requests is ".self::MAX_REQUESTS);
        }
        if (!$request->getId()) {
            $request->setId(Uuid::uuid4());
        }
        $this->requests[$request->getId()] = $request;
    }

    /**
     * @param RequestInformation $request
     * @throws \JsonException
     * @throws \League\Uri\Contracts\UriException
     */
    public function addRequestInformation(RequestInformation $request): void
    {
        $this->addRequest(new BatchRequestItem($request));
    }

    /**
     * @param RequestInterface $request
     * @throws \JsonException
     * @throws \League\Uri\Contracts\UriException
     */
    public function addPsrRequest(RequestInterface $request): void
    {
        $this->addRequest(BatchRequestItem::createWithPsrRequest($request));
    }

    public function remove(string $requestId)
    {
        if (!array_key_exists($requestId, $this->requests)) {
            throw new \InvalidArgumentException("Request with id:{$requestId} does NOT exist");
        }
        unset($this->requests[$requestId]);
    }

    public function removeBatchRequestItem(BatchRequestItem $item)
    {
        $this->remove($item->getId());
    }

    public function getFieldDeserializers(): array
    {
        return [];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeCollectionOfObjectValues("requests", array_values($this->requests));
    }
}
