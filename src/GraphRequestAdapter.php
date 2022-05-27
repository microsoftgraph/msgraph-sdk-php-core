<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core;


use GuzzleHttp\Client;
use Microsoft\Graph\Core\Core\Http\GraphClientFactory;
use Microsoft\Graph\Core\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Kiota\Abstractions\Authentication\AuthenticationProvider;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactory;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriterFactory;
use Microsoft\Kiota\Http\GuzzleRequestAdapter;

/**
 * Class GraphRequestAdapter
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphRequestAdapter extends GuzzleRequestAdapter
{
    /**
     * @param AuthenticationProvider|null $authenticationProvider
     * @param ParseNodeFactory|null $parseNodeFactory
     * @param SerializationWriterFactory|null $serializationWriterFactory
     * @param Client|null $guzzleClient
     * @param GraphTelemetryOption|null $telemetryOption
     */
    public function __construct(AuthenticationProvider $authenticationProvider, ?GraphTelemetryOption $telemetryOption = null, ?ParseNodeFactory $parseNodeFactory = null, ?SerializationWriterFactory $serializationWriterFactory = null, ?Client $guzzleClient = null)
    {
        $guzzleClient = ($guzzleClient) ?? GraphClientFactory::setTelemetryOption($telemetryOption)::create();
        parent::__construct($authenticationProvider, $parseNodeFactory, $serializationWriterFactory, $guzzleClient);
        $this->setBaseUrl('https://graph.microsoft.com/'.$telemetryOption->getApiVersion());
    }
}
