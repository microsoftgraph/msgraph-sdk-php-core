<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactory;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactoryRegistry;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use RuntimeException;
use UnexpectedValueException;

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
     * @var array<string,string[]|string>
     */
    private ?array $headers = [];

    /**
     * @var int|null
     */
    private ?int $statusCode = null;

    /**
     * @var array<string, BatchResponseItem>
     */
    private array $responses = [];

    public function __construct() {}

    /**
     * @return array<string, string[]|string>|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @param array<string, string[]|string>|null $headers
     */
    public function setHeaders(?array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     */
    public function setStatusCode(?int $statusCode): void
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
     * Gets a response for a given request ID
     * @param string $requestId
     * @return BatchResponseItem
     */
    public function getResponse(string $requestId): BatchResponseItem
    {
        if (!array_key_exists($requestId, $this->responses)) {
            throw new InvalidArgumentException("No response found for id: {$requestId}");
        }
        return $this->responses[$requestId];
    }

    /**
     * Deserializes a response item's body to $type. $type MUST implement Parsable
     *
     * @param string $requestId
     * @param string $type Parsable class name
     * @param ParseNodeFactory|null $parseNodeFactory checks the ParseNodeFactoryRegistry by default
     * @return Parsable|null
     */
    public function getResponseBody(string $requestId, string $type, ?ParseNodeFactory $parseNodeFactory = null): ?Parsable
    {
        if (!array_key_exists($requestId, $this->responses)) {
            throw new InvalidArgumentException("No response found for id: {$requestId}");
        }
        $interfaces = class_implements($type);
        if (!$interfaces || !in_array(Parsable::class, $interfaces)) {
            throw new InvalidArgumentException("Type passed must implement the Parsable interface");
        }
        $response = $this->responses[$requestId];
        if (!array_key_exists('content-type', $response->getHeaders() ?? [])) {
            throw new RuntimeException("Unable to get content-type header in response item");
        }
        $contentType = $response->getHeaders()['content-type'];
        $contentTypeNew = is_array($contentType) ? implode(',', $contentType) : $contentType;
        $responseBody = $response->getBody() ?? Utils::streamFor(null);
        if ($parseNodeFactory) {
            $parseNode = $parseNodeFactory->getRootParseNode($contentTypeNew, $responseBody);
        } else {
            // Check the registry or default to Json deserialization
            try {
                $parseNode = ParseNodeFactoryRegistry::getDefaultInstance()->getRootParseNode($contentTypeNew, $responseBody);
            } catch (UnexpectedValueException $ex) {
                $parseNode = (new JsonParseNodeFactory())->getRootParseNode($contentTypeNew, $responseBody);
            }
        }
        return $parseNode->getObjectValue([$type, 'createFromDiscriminatorValue']);
    }

    public function getFieldDeserializers(): array
    {
        return [
            /** @phpstan-ignore-next-line */
            'responses' => fn (ParseNode $n) => $this->setResponses($n->getCollectionOfObjectValues([BatchResponseItem::class, 'create']))
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeCollectionOfObjectValues('responses', $this->getResponses());
    }

    public static function create(ParseNode $parseNode): BatchResponseContent
    {
        return new BatchResponseContent();
    }
}
