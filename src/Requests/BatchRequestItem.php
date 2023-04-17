<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use InvalidArgumentException;
use League\Uri\Contracts\UriException;
use Microsoft\Kiota\Abstractions\RequestHeaders;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Class BatchRequestItem
 *
 * Individual request within a Batch Request
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BatchRequestItem implements Parsable
{
    /**
     * Unique identifier
     *
     * @var string
     */
    private string $id;

    /**
     * HTTP Method
     *
     * @var string
     */
    private string $method;

    /**
     * Relative request URL e.g. /users
     *
     * @var string
     */
    private string $url;

    /**
     * Request headers. Should contain content-type based on type of $body
     * e.g. JSON - application/json, Text - text/plain
     *
     * @var RequestHeaders
     */
    private RequestHeaders $headers;

    /**
     *
     * @var StreamInterface|null
     */
    private ?StreamInterface $body = null;

    /**
     * List of requests IDs or BatchRequestItems. Request executes only if dependent requests were successful.
     * If depends on a request with a different $atomicityGroup, then $atomicityGroup should be included here.
     *
     * @var array<string>|null
     */
    private ?array $dependsOn = null;

    /**
     * @param RequestInformation $requestInformation. Fluent Request Builder paths have create[Get|Put|Post|Delete|Patch]RequestInformation functions
     * @param string $id. Auto-generated by default.
     * @param array<string|BatchRequestItem>|null $dependsOn List of requests this request depends on
     * @throws UriException
     */
    public function __construct(RequestInformation $requestInformation, string $id = "", ?array $dependsOn = null)
    {
        if (!$requestInformation->httpMethod) {
            throw new InvalidArgumentException("HTTP method cannot be NULL/empty");
        }
        $this->id = ($id) ?: Uuid::uuid4();
        $this->method = $requestInformation->httpMethod;
        $this->setUrl($requestInformation->getUri());
        $this->headers = $requestInformation->getHeaders();
        $this->body = $requestInformation->content;
        $this->dependsOn($dependsOn);
    }

    /**
     * @param RequestInterface $psrRequest. MUST contain URL and HTTP method
     * @param string $id Auto-generated by default.
     * @param array<BatchRequestItem|string>|null $dependsOn List of requests this request depends on
     * @return BatchRequestItem
     * @throws UriException
     */
    public static function createWithPsrRequest(RequestInterface $psrRequest, string $id = "", ?array $dependsOn = null): BatchRequestItem
    {
        $requestInfo = new RequestInformation();
        $requestInfo->httpMethod = $psrRequest->getMethod();
        $requestInfo->setUri($psrRequest->getUri());
        $requestInfo->setHeaders($psrRequest->getHeaders());
        $requestInfo->content = $psrRequest->getBody();
        return new BatchRequestItem($requestInfo, $id, $dependsOn);
    }

    /**
     * Create dependency between Batch Request Items
     *
     * @param BatchRequestItem[]|string[]|null $requests list of request IDs or BatchRequestItems
     */
    public function dependsOn(?array $requests): void
    {
        if ($requests) {
            array_map(fn ($request) => $this->dependsOn [] = is_string($request) ? $request : $request->getId(), $requests);
        }
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        // Set relative URL
        $urlParts = parse_url($url);
        if (!$urlParts || !array_key_exists('path', $urlParts)) {
            throw new InvalidArgumentException("Invalid URL {$url}");
        }
        // Set relative URL
        // Remove API version
        $urlWithoutVersion = preg_replace("/\/(v1.0|beta)/", '', "{$urlParts['path']}");
        if (!$urlWithoutVersion) {
            throw new InvalidArgumentException(
                "Error occurred during regex replacement of API version in URL string: $url"
            );
        }
        $this->url = $urlWithoutVersion;
        $this->url .= (array_key_exists('query', $urlParts)) ? "?{$urlParts['query']}" : '';
        $this->url .= (array_key_exists('fragment', $urlParts)) ? "#{$urlParts['fragment']}" : '';
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers->getAll();
    }

    /**
     * @param array<string, array<string>|string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers->clear();
        $this->headers->putAll($headers);
    }

    /**
     * @return StreamInterface|null
     */
    public function getBody(): ?StreamInterface
    {
        return $this->body;
    }

    /**
     * @param StreamInterface|null $body
     */
    public function setBody(?StreamInterface $body): void
    {
        $this->body = $body;
    }

    /**
     * @return string[]|null
     */
    public function getDependsOn(): ?array
    {
        return $this->dependsOn;
    }

    public function getFieldDeserializers(): array
    {
        return [];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('id', $this->getId());
        $writer->writeStringValue('method', $this->getMethod());
        $writer->writeStringValue('url', $this->getUrl());
        $writer->writeCollectionOfPrimitiveValues('dependsOn', $this->getDependsOn());
        $headers = null;
        foreach ($this->getHeaders() as $key => $val) {
            $headers[$key] = implode(", ", $val);
        }
        $writer->writeAnyValue('headers', $headers);
        if ($this->getBody()) {
            // API expects JSON object or base 64 encoded value for the body
            // We JSON decode the stream contents so that the body is not written as a string
            $jsonObject = json_decode($this->getBody()->getContents(), true);
            $isJsonString = $jsonObject && (json_last_error() === JSON_ERROR_NONE);
            $this->getBody()->rewind();
            $writer->writeAnyValue(
                'body',
                $isJsonString ? $jsonObject : base64_encode($this->getBody()->getContents())
            );
        }
    }
}
