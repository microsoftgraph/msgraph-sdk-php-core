<?php

namespace Microsoft\Graph\Core\Test\Authentication;

use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
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

}
