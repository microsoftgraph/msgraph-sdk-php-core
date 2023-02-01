<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Authentication;


use Microsoft\Kiota\Authentication\Oauth\TokenRequestContext;
use Microsoft\Kiota\Authentication\PhpLeagueAccessTokenProvider;

/**
 * Class GraphPhpLeagueAccessTokenProvider
 *
 * Fetches an access token using the PHP League OAuth 2.0 client library while setting default Graph allowed hosts
 *
 * @package Microsoft\Graph\Core\Authentication
 * @copyright 2023 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphPhpLeagueAccessTokenProvider extends PhpLeagueAccessTokenProvider
{
    /**
     * @param TokenRequestContext $tokenRequestContext
     * @param array<string> $scopes if left empty, it's set to ["https://[graph national cloud host]/.default"] scope
     */
    public function __construct(TokenRequestContext $tokenRequestContext, array $scopes = [])
    {
        $allowedHosts = ["graph.microsoft.com", "graph.microsoft.us", "dod-graph.microsoft.us", "graph.microsoft.de",
            "microsoftgraph.chinacloudapi.cn", "canary.graph.microsoft.com"];
        parent::__construct($tokenRequestContext, $scopes, $allowedHosts);
    }
}
