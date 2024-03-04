<?php

namespace Microsoft\Graph\Core\Test\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Kiota\Authentication\Cache\InMemoryAccessTokenCache;
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

    public function testTokenServiceDefaultsToAzureADGlobalEndpoint(): void
    {
        $tokenProvider = new GraphPhpLeagueAccessTokenProvider(
            new ClientCredentialContext('tenant', 'client', 'secret'),
            [],
            'https://canary.microsoft.com'
        );
        $baseUrl = GraphPhpLeagueAccessTokenProvider::NATIONAL_CLOUD_TO_AZURE_AD_ENDPOINT[NationalCloud::GLOBAL];
        $this->assertEquals("$baseUrl/tenant/oauth2/v2.0/token", $tokenProvider->getOauthProvider()->getBaseAccessTokenUrl([]));
        $this->assertEquals("$baseUrl/tenant/oauth2/v2.0/authorize", $tokenProvider->getOauthProvider()->getBaseAuthorizationUrl());
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

    public function testCreateWithCache(): void
    {
        $tokenRequestContext = new ClientCredentialContext('tenant', 'clientId', 'secret');
        $cache = new InMemoryAccessTokenCache();
        $tokenProvider = GraphPhpLeagueAccessTokenProvider::createWithCache($cache, $tokenRequestContext, ['https://graph.microsoft.com/.default']);
        $mockResponses = [
            function (Request $request) use ($tokenRequestContext) {
                parse_str($request->getBody()->getContents(), $requestBodyMap);
                $expectedBody = array_merge($tokenRequestContext->getParams(), [
                    'scope' => 'https://graph.microsoft.com/.default'
                ]);
                $this->assertEquals($expectedBody, $requestBodyMap);
                return new Response(200, [], json_encode(['access_token' => 'xyz', 'expires_in' => 1]));
            },
        ];
        $tokenProvider->getOauthProvider()->setHttpClient(new Client(['handler' => new MockHandler($mockResponses)]));
        $tokenProvider->getAuthorizationTokenAsync('https://graph.microsoft.com/me');
        $this->assertEquals('xyz', $cache->getTokenWithContext($tokenRequestContext)->getToken());
    }

}
