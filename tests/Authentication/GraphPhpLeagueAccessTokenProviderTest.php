<?php

namespace Microsoft\Graph\Core\Test\Authentication;

use League\OAuth2\Client\Token\AccessToken;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use PHPUnit\Framework\TestCase;

class GraphPhpLeagueAccessTokenProviderTest extends TestCase
{
    public function testSuccessfullyInitializesClass(): void
    {
        $context = new ClientCredentialContext('tenant', 'clientId', 'secret');
        $leagueAccessTokenProvider = new GraphPhpLeagueAccessTokenProvider($context, []);
        $this->assertNotEmpty($leagueAccessTokenProvider->getAllowedHostsValidator()->getAllowedHosts());
    }

    public function testInvalidNationalCloudThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $tokenProvider = new GraphPhpLeagueAccessTokenProvider(
            new ClientCredentialContext('tenant', 'client', 'secret'),
            [],
            'https://canary.microsoft.com'
        );
    }

    public function testCorrectOAuthEndpointsSet(): void
    {
        $tokenProvider = new GraphPhpLeagueAccessTokenProvider(
            new ClientCredentialContext('tenant', 'client', 'secret'),
            [],
            NationalCloud::CHINA
        );
        $baseUrl = GraphPhpLeagueAccessTokenProvider::NATIONAL_CLOUD_TO_AZURE_AD_ENDPOINT[NationalCloud::CHINA];
        $this->assertEquals(NationalCloud::CHINA."/oidc/userinfo", $tokenProvider->getOauthProvider()->getResourceOwnerDetailsUrl(
            $this->createMock(AccessToken::class)
        ));
        $this->assertEquals("$baseUrl/tenant/oauth2/v2.0/token", $tokenProvider->getOauthProvider()->getBaseAccessTokenUrl([]));
        $this->assertEquals("$baseUrl/tenant/oauth2/v2.0/authorize", $tokenProvider->getOauthProvider()->getBaseAuthorizationUrl());
    }

}
