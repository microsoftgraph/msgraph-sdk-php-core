<?php

namespace Microsoft\Graph\Core\Test\Authentication;

use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use PHPUnit\Framework\TestCase;

class GraphPhpLeagueAuthenticationProviderTest extends TestCase
{
    public function testSuccessfullyInitializesClass(): void
    {
        $context = new ClientCredentialContext('tenant', 'clientId', 'secret');
        $leagueAuthProvider = new GraphPhpLeagueAuthenticationProvider($context, []);
        $this->assertNotEmpty($leagueAuthProvider->getAccessTokenProvider()->getAllowedHostsValidator()->getAllowedHosts());
    }

    public function testNationalCloudUrlIsUsed(): void
    {
        $context = new ClientCredentialContext('tenant', 'clientId', 'secret');
        $leagueAuthProvider = new GraphPhpLeagueAuthenticationProvider($context, [], NationalCloud::US_GOV);
        $this->assertEquals('https://login.microsoftonline.us/tenant/oauth2/v2.0/authorize', $leagueAuthProvider->getAccessTokenProvider()->getOauthProvider()->getBaseAuthorizationUrl());
        $this->assertEquals('https://login.microsoftonline.us/tenant/oauth2/v2.0/token', $leagueAuthProvider->getAccessTokenProvider()->getOauthProvider()->getBaseAccessTokenUrl([]));
    }
}
