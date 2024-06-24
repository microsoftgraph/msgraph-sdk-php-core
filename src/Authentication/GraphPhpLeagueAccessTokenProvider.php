<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Authentication;


use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Authentication\Cache\AccessTokenCache;
use Microsoft\Kiota\Authentication\Oauth\ProviderFactory;
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
    public const NATIONAL_CLOUD_TO_AZURE_AD_ENDPOINT = [
        NationalCloud::GLOBAL => 'https://login.microsoftonline.com',
        NationalCloud::US_GOV => 'https://login.microsoftonline.us',
        NationalCloud::CHINA => 'https://login.chinacloudapi.cn'
    ];

    /**
     * @param TokenRequestContext $tokenRequestContext
     * @param array<string> $scopes if left empty, it's set to ["https://[graph national cloud host]/.default"] scope
     * @param string $nationalCloud Defaults to https://graph.microsoft.com. See
     * https://learn.microsoft.com/en-us/graph/deployments
     * @param AccessTokenCache|null $accessTokenCache Defaults to an in-memory cache if null
     */
    public function __construct(
        TokenRequestContext $tokenRequestContext,
        array $scopes = [],
        string $nationalCloud = NationalCloud::GLOBAL,
        ?AccessTokenCache $accessTokenCache = null
    )
    {
        $nationalCloud = empty($nationalCloud) ? NationalCloud::GLOBAL : $nationalCloud;
        $allowedHosts = [
            "graph.microsoft.com",
            "graph.microsoft.us",
            "dod-graph.microsoft.us",
            "microsoftgraph.chinacloudapi.cn",
            "canary.graph.microsoft.com",
            "graph.microsoft-ppe.com"
        ];
        $tokenBaseServiceUrl = self::NATIONAL_CLOUD_TO_AZURE_AD_ENDPOINT[$nationalCloud] ??
            self::NATIONAL_CLOUD_TO_AZURE_AD_ENDPOINT[NationalCloud::GLOBAL];
        $oauthProvider = ProviderFactory::create(
            $tokenRequestContext,
            [],
            $tokenBaseServiceUrl,
            $nationalCloud
        );
        parent::__construct($tokenRequestContext, $scopes, $allowedHosts, $oauthProvider, $accessTokenCache);
    }

    /**
     * Get an instance of GraphPhpLeagueAccessTokenProvider with a custom cache
     *
     * @param AccessTokenCache $accessTokenCache
     * @param TokenRequestContext $tokenRequestContext
     * @param array<string> $scopes
     * @return GraphPhpLeagueAccessTokenProvider
     */
    public static function createWithCache(
        AccessTokenCache $accessTokenCache,
        TokenRequestContext $tokenRequestContext,
        array $scopes = []
    ): self
    {
        return new GraphPhpLeagueAccessTokenProvider(
            $tokenRequestContext, $scopes, NationalCloud::GLOBAL, $accessTokenCache);
    }
}
