<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\RequestInformation;
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
     * @var array<string, BatchResponseItem>|null
     */
    private ?array $responses = [];

    public function __construct() {}

    /**
     * @return BatchResponseItem[]|null
     */
    public function getResponses(): ?array
    {
        return is_null($this->responses) ? null : array_values($this->responses);
    }

    /**
     * @param BatchResponseItem[]|null $responses
     */
    public function setResponses(?array $responses): void
    {
        if (is_array($responses)) {
            array_map(fn ($response) => $this->responses[$response->getId()] = $response, $responses);
            return;
        }
        $this->responses = $responses;
    }

    /**
     * Gets a response for a given request ID
     * @param string $requestId
     * @return BatchResponseItem
     */
    public function getResponse(string $requestId): BatchResponseItem
    {
        if (!$this->responses || !array_key_exists($requestId, $this->responses)) {
            throw new InvalidArgumentException("No response found for id: {$requestId}");
        }
        return $this->responses[$requestId];
    }

    /**
     * Deserializes a response item's body to $type. $type MUST implement Parsable
     *
     * @template T of Parsable
     * @param string $requestId
     * @param class-string<T> $type Parsable class name
     * @param ParseNodeFactory|null $parseNodeFactory checks the ParseNodeFactoryRegistry by default
     * @return T|null
     */
    public function getResponseBody(string $requestId, string $type, ?ParseNodeFactory $parseNodeFactory = null): ?Parsable
    {
        if (!$this->responses || !array_key_exists($requestId, $this->responses)) {
            throw new InvalidArgumentException("No response found for id: {$requestId}");
        }
        $interfaces = class_implements($type);
        if (!$interfaces || !in_array(Parsable::class, $interfaces)) {
            throw new InvalidArgumentException("Type passed must implement the Parsable interface");
        }
        $response = $this->responses[$requestId];
        $contentType = $response->getContentType();
        if (!$contentType) {
            throw new RuntimeException("Unable to get content-type header in response item");
        }
        $responseBody = $response->getBody() ?? Utils::streamFor(null);
        if ($parseNodeFactory) {
            $parseNode = $parseNodeFactory->getRootParseNode($contentType, $responseBody);
        } else {
            // Check the registry or default to Json deserialization
            try {
                $parseNode = ParseNodeFactoryRegistry::getDefaultInstance()->getRootParseNode($contentType, $responseBody);
            } catch (\Exception $ex) {
                $responseBody->rewind();
                $response->setBody(Utils::streamFor(base64_decode($responseBody->getContents())));
                $responseBody = $response->getBody() ?? Utils::streamFor(null);
                $parseNode = ParseNodeFactoryRegistry::getDefaultInstance()->getRootParseNode($contentType, $responseBody);
            }
        }
        return $parseNode->getObjectValue([$type, 'createFromDiscriminatorValue']);
    }

    public function getFieldDeserializers(): array
    {
        return [
            'responses' => fn (ParseNode $n) => $this->setResponses($n->getCollectionOfObjectValues([BatchResponseItem::class, 'create']))
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeCollectionOfObjectValues('responses', $this->getResponses());
    }

    public static function createFromDiscriminatorValue(ParseNode $parseNode): BatchResponseContent
    {
        return new BatchResponseContent();
    }
}
