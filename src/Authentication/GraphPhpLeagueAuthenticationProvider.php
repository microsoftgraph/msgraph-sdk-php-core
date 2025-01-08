<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Authentication;

use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Authentication\Oauth\TokenRequestContext;
use Microsoft\Kiota\Authentication\PhpLeagueAuthenticationProvider;

/**
 * Class GraphPhpLeagueAuthenticationProvider
 * @package Microsoft\Graph\Core\Authentication
 * @copyright 2023 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphPhpLeagueAuthenticationProvider extends PhpLeagueAuthenticationProvider
{
    /**
     * @param TokenRequestContext $tokenRequestContext
     * @param array<string> $scopes defaults to ["https://[graph national cloud host]/.default"] scope
     * @param string $nationalCloud @deprecated Parameter is not passed up to the parent class. Use createWithAccessTokenProvider() instead
     */
    public function __construct(
        TokenRequestContext $tokenRequestContext,
        array $scopes = [],
        string $nationalCloud = NationalCloud::GLOBAL // @deprecated parameter is not passed up to the parent class. Use createWithAccessTokenProvider() instead
    )
    {
        $accessTokenProvider = new GraphPhpLeagueAccessTokenProvider($tokenRequestContext, $scopes, $nationalCloud);
        parent::__construct($tokenRequestContext, $scopes, $accessTokenProvider->getAllowedHosts());
    }

}
