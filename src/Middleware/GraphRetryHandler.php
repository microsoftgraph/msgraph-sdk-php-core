<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Core\Middleware;


use GuzzleHttp\Promise\PromiseInterface;
use Microsoft\Kiota\Http\Middleware\RetryHandler;
use Psr\Http\Message\RequestInterface;

/**
 * Class GraphRetryHandler
 * @package Microsoft\Graph\Core\Middleware
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphRetryHandler extends RetryHandler
{
    use FeatureFlagTrait;

    const FEATURE_FLAG = 0x00000002;

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $this->setFeatureFlag(self::FEATURE_FLAG, $options);
        return parent::__invoke($request, $options);
    }
}
