<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Http;


class GraphError
{
    /**
     * Contains all properties of the error response
     *
     * @var array
     */
    private $propDict;

    public function __construct(array $propDict) {
        $this->propDict = $propDict;
    }

    /**
     * Get error code returned by the Graph
     *
     * @return string|null
     */
    public function code(): ?string {
        if (array_key_exists("code", $this->propDict)) {
            return $this->propDict["code"];
        }
        return null;
    }

    /**
     * Get error message returned by the Graph
     *
     * @return string|null
     */
    public function message(): ?string {
        if (array_key_exists("message", $this->propDict)) {
            return $this->propDict["message"];
        }
        return null;
    }

    /**
     * Get the additional error info
     *
     * @return GraphError|null
     */
    public function innerError(): ?GraphError {
        if (array_key_exists("innerError", $this->propDict)) {
            return new GraphError($this->propDict["innerError"]);
        }
        return null;
    }

    /**
     * Returns the client request Id
     *
     * @return string|null
     */
    public function clientRequestId(): ?string {
        if (array_key_exists("client-request-id", $this->propDict)) {
            return $this->propDict["client-request-id"];
        }
        return null;
    }

    /**
     * Returns the request Id
     *
     * @return string|null
     */
    public function requestId(): ?string {
        if (array_key_exists("request-id", $this->propDict)) {
            return $this->propDict["request-id"];
        }
        return null;
    }

    /**
     * Returns the date of the request
     *
     * @return string|null
     */
    public function date(): ?string {
        if (array_key_exists("date", $this->propDict)) {
            return $this->propDict["date"];
        }
        return null;
    }

    /**
     * Returns all properties passed to the constructor
     *
     * @return array
     */
    public function getProperties(): array {
        return $this->propDict;
    }
}
