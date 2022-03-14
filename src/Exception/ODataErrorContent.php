<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Exception;

/**
 * Class ODataError
 *
 * Defines error structure of an OData v4 Error based on http://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html#_Toc31358908
 *
 * @package Microsoft\Graph\Exception
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class ODataErrorContent extends BaseErrorContent
{
    /**
     * Get error code returned by the Graph
     *
     * @return string|null
     */
    public function getCode(): ?string {
        return $this->getProperty("code");
    }

    /**
     * Get error message returned by the Graph
     *
     * @return string|null
     */
    public function getMessage(): ?string {
        return $this->getProperty("message");
    }

    /**
     * Returns the target of the error
     *
     * @return string|null
     */
    public function getTarget(): ?string {
        return $this->getProperty("target");
    }

    /**
     * Returns the error details containing a code, message and target
     *
     * @return ODataErrorContent[]|null
     */
    public function getDetails(): ?array {
        $details = $this->getProperty("details");
        if ($details) {
            return array_map(function ($detail) { return new ODataErrorContent($detail); }, $details);
        }
        return null;
    }

    /**
     * Get the Graph-specific error info
     *
     * @return GraphErrorContent|null
     */
    public function getInnerError(): ?GraphErrorContent {
        $innerError = $this->getProperty("innerError");
        return ($innerError) ? new GraphErrorContent($innerError) : null;
    }

    /**
     * Returns error as a string
     *
     * @return string
     */
    public function __toString(): string {
        $errorString = ($this->getCode()) ? "Code: ".$this->getCode() : "";
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
