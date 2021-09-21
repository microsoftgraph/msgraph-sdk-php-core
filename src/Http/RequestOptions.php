<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Http;

/**
 * Class RequestOptions
 * Configure custom properties to add to the request
 *
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class RequestOptions
{
    /**
     * @var array<string, string|string[]> custom headers to add to the request
     */
    private $headers = [];

    /**
     * RequestOptions constructor.
     * @param array<string, string|string[]> $headers custom headers to add to the request
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    /**
     * Gets the headers to add to the request
     *
     * @return array<string, string|string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

}
