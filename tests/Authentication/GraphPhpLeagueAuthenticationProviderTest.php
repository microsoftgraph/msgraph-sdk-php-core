<?php

namespace Microsoft\Graph\Core\Test\Authentication;

use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
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
}
