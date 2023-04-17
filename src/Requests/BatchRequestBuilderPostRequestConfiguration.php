<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;


use Microsoft\Kiota\Abstractions\RequestOption;

/**
 * Class BatchRequestBuilderPostRequestConfiguration
 * @package Microsoft\Graph\Core\Requests
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BatchRequestBuilderPostRequestConfiguration
{
    /**
     * @var array<string, array<string>|string>|null $headers Request headers
     */
    public ?array $headers = null;

    /**
     * @var array<RequestOption>|null $options Request options
     */
    public ?array $options = null;
}
