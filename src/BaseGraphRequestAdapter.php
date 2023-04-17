<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core;


use GuzzleHttp\Client;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Kiota\Abstractions\Authentication\AnonymousAuthenticationProvider;
use Microsoft\Kiota\Abstractions\Authentication\AuthenticationProvider;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactory;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriterFactory;
use Microsoft\Kiota\Http\GuzzleRequestAdapter;

/**
 * Class BaseGraphRequestAdapter
 *
 * @package Microsoft\Graph\Core
 * @copyright 2022 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class BaseGraphRequestAdapter extends GuzzleRequestAdapter
{
    /**
     * @param GraphTelemetryOption $telemetryOption
     * @param AuthenticationProvider|null $authenticationProvider
     * @param ParseNodeFactory|null $parseNodeFactory
     * @param SerializationWriterFactory|null $serializationWriterFactory
     * @param Client|null $guzzleClient
     */
    public function __construct(GraphTelemetryOption $telemetryOption,
                                ?AuthenticationProvider $authenticationProvider = null,
                                ?ParseNodeFactory $parseNodeFactory = null,
                                ?SerializationWriterFactory $serializationWriterFactory = null,
                                ?Client $guzzleClient = null)
    {
        $authenticationProvider = ($authenticationProvider) ?? new AnonymousAuthenticationProvider();
        $guzzleClient = ($guzzleClient) ?? GraphClientFactory::setTelemetryOption($telemetryOption)::create();
        parent::__construct($authenticationProvider, $parseNodeFactory, $serializationWriterFactory, $guzzleClient);
        $this->setBaseUrl('https://graph.microsoft.com/'.$telemetryOption->getApiVersion());
    }
}
