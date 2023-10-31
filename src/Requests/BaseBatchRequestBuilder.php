<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;

use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;

/**
 * Class BaseBatchRequestBuilder
 * @template T of Parsable
 * @package Microsoft\Graph\Core\Requests
 * @copyright 2023 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BaseBatchRequestBuilder
{
    /**
     * @var RequestAdapter
     */
    private RequestAdapter $requestAdapter;

    /**
     * @var array<string, array{class-string<T>, string}>|null Error models per status code range to deserialize
     *  failed batch request payloads to
     * e.g. ['4XX' => [Parsable that extends ApiException, static factory method in error model]]
     */
    private ?array $errorMappings;

    /**
     * @var string
     */
    private string $urlTemplate = '{+baseurl}/$batch';

    /**
     * @param RequestAdapter $requestAdapter
     * @param array<string, array{class-string<T>, string}>|null $errorMappings
     */
    public function __construct(RequestAdapter $requestAdapter, array $errorMappings = null)
    {
        $this->requestAdapter = $requestAdapter;
        $this->errorMappings = $errorMappings;
    }

    /**
     * @param BatchRequestContent $body
     * @param BatchRequestBuilderPostRequestConfiguration|null $requestConfiguration
     * @return RequestInformation
     */
    public function toPostRequestInformation(BatchRequestContent $body,
                                             ?BatchRequestBuilderPostRequestConfiguration $requestConfiguration = null): RequestInformation
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
     * @return Promise<BatchResponseContent|null>
     */
    public function postAsync(BatchRequestContent $body,
                              ?BatchRequestBuilderPostRequestConfiguration $requestConfig = null): Promise
    {
        $requestInfo = $this->toPostRequestInformation($body, $requestConfig);
        try {
            return $this->requestAdapter->sendAsync($requestInfo,
                [BatchResponseContent::class, 'createFromDiscriminatorValue'],
                $this->errorMappings);
        } catch (\Exception $ex) {
            return new RejectedPromise($ex);
        }
    }
}
