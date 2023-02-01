<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Authentication;

use Microsoft\Kiota\Abstractions\Authentication\BaseBearerTokenAuthenticationProvider;
use Microsoft\Kiota\Authentication\Oauth\TokenRequestContext;

/**
 * Class GraphPhpLeagueAuthenticationProvider
 * @package Microsoft\Graph\Core\Authentication
 * @copyright 2023 Microsoft Corporation
 * @license https://opensource.org/licenses/MIT MIT License
 * @link https://developer.microsoft.com/graph
 */
class GraphPhpLeagueAuthenticationProvider extends BaseBearerTokenAuthenticationProvider
{
    /**
     * @param TokenRequestContext $tokenRequestContext
     * @param array<string> $scopes defaults to ["https://[graph national cloud host]/.default"] scope
     */
    public function __construct(TokenRequestContext $tokenRequestContext, array $scopes = [])
    {
        $accessTokenProvider = new GraphPhpLeagueAccessTokenProvider($tokenRequestContext, $scopes);
        parent::__construct($accessTokenProvider);
    }
}
