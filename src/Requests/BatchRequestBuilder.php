<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use GuzzleHttp\Psr7\Response;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\NativeResponseHandler;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\ResponseHandler;
use Microsoft\Kiota\Http\GuzzleRequestAdapter;
use Microsoft\Kiota\Http\Middleware\Options\ResponseHandlerOption;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use Psr\Http\Message\ResponseInterface;

/**
 * Class BatchRequestBuilder
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BatchRequestBuilder
{
    /**
     * @var RequestAdapter
     */
    private RequestAdapter $requestAdapter;

    /**
     * @var string
     */
    private string $urlTemplate = '{+baseurl}/$batch';

    /**
     * @param RequestAdapter $requestAdapter
     */
    public function __construct(RequestAdapter $requestAdapter)
    {
        $this->requestAdapter = $requestAdapter;
    }

    /**
     * @param BatchRequestContent $body
     * @param BatchRequestBuilderPostRequestConfiguration|null $requestConfiguration
     * @return RequestInformation
     */
    public function toPostRequestInformation(BatchRequestContent $body, ?BatchRequestBuilderPostRequestConfiguration $requestConfiguration = null): RequestInformation
    {
        $requestInfo = new RequestInformation();
        $requestInfo->urlTemplate = $this->urlTemplate;
        $requestInfo->httpMethod = HttpMethod::POST;
        $requestInfo->addHeader("Accept", "application/json");
        if ($requestConfiguration !== null) {
            if ($requestConfiguration->headers !== null) {
                $requestInfo->addHeaders($requestConfiguration->headers);
            }
            if ($requestConfiguration->options !== null) {
                $requestInfo->addRequestOptions(...$requestConfiguration->options);
            }
        }
        $requestInfo->setContentFromParsable($this->requestAdapter, "application/json", $body);
        return $requestInfo;
    }

    /**
     * @param BatchRequestContent $body
     * @param BatchRequestBuilderPostRequestConfiguration|null $requestConfig
     * @return Promise
     */
    public function postAsync(BatchRequestContent $body, ?BatchRequestBuilderPostRequestConfiguration $requestConfig = null): Promise
    {
        $requestInfo = $this->toPostRequestInformation($body, $requestConfig);
        $requestInfo->addRequestOptions(new ResponseHandlerOption(new NativeResponseHandler()));
        try {
            return $this->requestAdapter->sendAsync($requestInfo, [BatchResponseContent::class, 'create'])->then(
                function (Promise $promise) {
                    $response = $promise->wait();
                    if (is_object($response) && is_a($response, Response::class)) {
                        $rootParseNode = $this->requestAdapter->getParseNodeFactory()->getRootParseNode('application/json', $response->getBody());
                        /** @var BatchResponseContent $batchResponseContent */
                        $batchResponseContent = $rootParseNode->getObjectValue([BatchResponseContent::class, 'create']);
                        $batchResponseContent->setStatusCode($response->getStatusCode());
                        $batchResponseContent->setHeaders($response->getHeaders());
                        return $batchResponseContent;
                    }
                }
            );
        } catch (\Exception $ex) {
            return new RejectedPromise($ex);
        }
    }
}
