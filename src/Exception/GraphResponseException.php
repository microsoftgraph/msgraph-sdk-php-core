<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Exception;

use Microsoft\Graph\Core\Http\GraphRequest;
use Psr\Http\Message\StreamInterface;

/**
 * Class GraphServiceException
 *
 * Thrown when the Graph API returns 4xx/5xx responses
 *
 * @package Microsoft\Graph\Exception
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphResponseException extends \Exception
{
    /**
     * Headers returned in the response
     *
     * @var array
     */
    private $responseHeaders;
    /**
     * HTTP response code
     *
     * @var int
     */
    private $responseStatusCode;
    /**
     * Raw response body
     *
     * @var StreamInterface
     */
    private $responseStream;
    /**
     * JSON_decoded response body
     *
     * @var array|null
     */
    private $jsonBody;
    /**
     * The request that triggered the error response
     *
     * @var GraphRequest
     */
    private $graphRequest;


    /**
     * GraphServiceException constructor.
     * @param GraphRequest $graphRequest
     * @param int $responseStatusCode
     * @param StreamInterface $responseStream
     * @param array $responseHeaders
     */
    public function __construct(
        GraphRequest    $graphRequest,
        int             $responseStatusCode,
        StreamInterface $responseStream,
        array           $responseHeaders
    ) {
        $this->graphRequest = $graphRequest;
        $this->responseStatusCode = $responseStatusCode;
        $this->responseStream = $responseStream;
        $this->setJsonBody();
        $this->responseHeaders = $responseHeaders;
        $message = "'".$graphRequest->getRequestType()."' request to ".$graphRequest->getRequestUri()." returned ".$responseStatusCode;
        parent::__construct($message, $responseStatusCode);
    }

    /**
     * Returns HTTP headers in the response from the Graph
     *
     * @return array<string, string[]>
     */
    public function getResponseHeaders(): array {
        return $this->responseHeaders;
    }

    /**
     * Returns HTTP status code returned int the response from the Graph
     *
     * @return int
     */
    public function getResponseStatusCode(): int {
        return $this->responseStatusCode;
    }

    /**
     * Returns the raw response stream
     *
     * @return StreamInterface
     */
    public function getRawResponseBody(): StreamInterface {
        return $this->responseStream;
    }

    /**
     * Get the response stream contents as a string
     *
     * @return string
     * @throws \RuntimeException if the stream couldn't be read/rewind() failed
     */
    public function getResponseBodyAsString(): string {
        $this->responseStream->rewind();
        return $this->responseStream->getContents();
    }

    /**
     * Returns the JSON-decoded response payload from the Graph
     * If payload could not be JSON-decoded null is returned. Consider getResponseBodyAsString() or getRawResponseBody()
     *
     * @return array|null
     */
    public function getResponseBodyJson(): ?array {
        return $this->jsonBody;
    }

    /**
     * Returns the error object of the payload
     *
     * @return ODataErrorContent|null
     */
    public function getError(): ?ODataErrorContent {
        if (is_array($this->jsonBody) && array_key_exists("error", $this->jsonBody)) {
            return new ODataErrorContent($this->jsonBody["error"]);
        }
        return null;
    }

    /**
     * Returns the request that triggered the error response
     *
     * @return GraphRequest
     */
    public function getRequest(): GraphRequest {
        return $this->graphRequest;
    }

    /**
     * Returns the client request Id
     *
     * @return string|null
     */
    public function getClientRequestId(): ?string {
        $headerName = "client-request-id";
        if (array_key_exists($headerName, $this->responseHeaders)
            && !empty($this->responseHeaders[$headerName])) {
            return $this->responseHeaders[$headerName][0];
        }
        return null;
    }

    /**
     * Returns the request Id
     *
     * @return string|null
     */
    public function getRequestId(): ?string {
        $headerName = "request-id";
        if (array_key_exists($headerName, $this->responseHeaders)
            && !empty($this->responseHeaders[$headerName])) {
            return $this->responseHeaders[$headerName][0];
        }
        return null;
    }

    /**
     * Reads the entire stream's contents and decodes the string
     */
    private function setJsonBody(): void {
        $this->responseStream->rewind();
        try {
            $this->jsonBody = json_decode($this->responseStream->getContents(), true);
        } catch (\RuntimeException $ex) {
            $this->jsonBody = null;
        }
    }
}
