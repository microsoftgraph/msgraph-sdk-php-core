<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */

namespace Microsoft\Graph\Core\Core\Http;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;

/**
 * Interface HttpClientInterface
 *
 * Provides methods for making synchronous and asynchronous requests on an HTTP client
 *
 * @package Microsoft\Graph\Http
 * @copyright 2021 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
interface HttpClientInterface extends HttpClient, HttpAsyncClient
{

}
