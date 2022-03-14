<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Exception;

/**
 * Class GraphError
 *
 * Contains Graph service specific error content defined in the "innerError" of an ODataError
 *
 * @package Microsoft\Graph\Exception
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphErrorContent extends ODataErrorContent
{
    /**
     * Returns the date of the request
     *
     * @return string|null
     */
    public function getDate(): ?string {
        return $this->getProperty("date");
    }

    /**
     * Returns the client request id
     *
     * @return string|null
     */
    public function getClientRequestId(): ?string {
        return $this->getProperty("client-request-id");
    }

    /**
     * Returns the request id
     *
     * @return string|null
     */
    public function getRequestId(): ?string {
        return $this->getProperty("request-id");
    }

    /**
     * Returns error as a string
     *
     * @return string
     */
    public function __toString(): string {
        $errorString = ($this->getDate()) ? "Date: ".$this->getDate() : "";
        $errorString .= ($this->getClientRequestId()) ? "\nClient Request Id: ".$this->getClientRequestId() : "";
        $errorString .= ($this->getRequestId()) ? "\nRequest Id: ".$this->getRequestId() : "";
        $errorString .= ($this->getCode()) ? "\nCode: ".$this->getCode() : "";
        $errorString .= ($this->getMessage()) ? "\nMessage: ".$this->getMessage() : "";
        $errorString .= ($this->getTarget()) ? "\nTarget: ".$this->getTarget() : "";
        if ($this->getDetails()) {
            $details = array_map(function ($detail) { return strval($detail); }, $this->getDetails());
            $errorString .= implode(",", $details);
        }
        $errorString .= ($this->getInnerError()) ? "\nInner Error: ".$this->getInnerError() : "";
        return $errorString;
    }
}
