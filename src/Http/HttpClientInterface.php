<?php

namespace Microsoft\Graph\Http;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;

/**
 * Interface HttpClientInterface
 * Provides methods for making synchronous and asynchronous requests on an HTTP client
 *
 * @package Microsoft\Graph\Http
 */
interface HttpClientInterface extends HttpClient, HttpAsyncClient
{

}
