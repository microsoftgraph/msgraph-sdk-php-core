<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

/**
 * Class BatchResponseContent
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BatchResponseContent implements Parsable
{
    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var array<string, BatchResponseItem>
     */
    private array $responses = [];

    /**
     * @var string
     */
    private string $nextLink = "";

    public function __construct() {}

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return BatchResponseItem[]
     */
    public function getResponses(): array
    {
        return array_values($this->responses);
    }

    /**
     * @param BatchResponseItem[] $responses
     */
    public function setResponses(array $responses): void
    {
        array_map(fn ($response) => $this->responses[$response->getId()] = $response, $responses);
    }

    /**
     * @return string
     */
    public function getNextLink(): string
    {
        return $this->nextLink;
    }

    /**
     * Gets a response for a given request ID
     * @param string $requestId
     * @return BatchResponseItem
     */
    public function getResponse(string $requestId): BatchResponseItem
    {
        if (!array_key_exists($requestId, $this->responses)) {
            throw new \InvalidArgumentException("No response found for id: {$requestId}");
        }
        return $this->responses[$requestId];
    }

    /**
     * @param string $nextLink
     */
    public function setNextLink(string $nextLink): void
    {
        $this->nextLink = $nextLink;
    }

    public function getFieldDeserializers(): array
    {
        return [
            '@nextLink' => fn (ParseNode $n) => $this->setNextLink($n->getStringValue()),
            'responses' => fn (ParseNode $n) => $this->setResponses($n->getCollectionOfObjectValues([BatchResponseItem::class, 'create']))
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('@nextLink', $this->getNextLink());
        $writer->writeCollectionOfObjectValues('responses', [BatchResponseItem::class, 'create']);
    }

    public static function create(): BatchResponseContent
    {
        return new BatchResponseContent();
    }
}
