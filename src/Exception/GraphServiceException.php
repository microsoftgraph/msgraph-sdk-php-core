<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Exception;

use Microsoft\Graph\Http\GraphRequest;

/**
 * Class GraphServiceException
 *
 * Thrown when the Graph API returns 4xx or 5xx responses
 *
 * @package Microsoft\Graph\Exception
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphServiceException extends GraphException
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
     * JSON-decoded response body
     *
     * @var array
     */
    private $responseBody;
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
     * @param array $responseBody
     * @param array $responseHeaders
     */
    public function __construct(
        GraphRequest $graphRequest,
        int $responseStatusCode,
        array $responseBody,
        array $responseHeaders
    ) {
        $this->graphRequest = $graphRequest;
        $this->responseStatusCode = $responseStatusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
        $message = "'".$graphRequest->getRequestType()."' request to ".$graphRequest->getRequestUri()." returned ".$responseStatusCode."\n".json_encode($responseBody);
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
     * Get JSON-decoded response payload from the Graph
     *
     * @return array
     */
    public function getRawResponseBody(): array {
        return $this->responseBody;
    }

    /**
     * Returns the error object of the payload
     *
     * @return ODataError|null
     */
    public function getError(): ?ODataError {
        if (array_key_exists("error", $this->responseBody)) {
            return new ODataError($this->responseBody["error"]);
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
}
