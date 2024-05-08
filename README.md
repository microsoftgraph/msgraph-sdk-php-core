# Get started with the Microsoft Graph Core SDK for PHP

[![Latest Stable Version](https://poser.pugx.org/microsoft/microsoft-graph-core/version)](https://packagist.org/packages/microsoft/microsoft-graph-core)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=microsoftgraph_msgraph-sdk-php-core&metric=coverage)](https://sonarcloud.io/dashboard?id=microsoftgraph_msgraph-sdk-php-core)

## Install the Core Library
To install the `microsoft-graph-core` library with Composer, either run `composer require microsoft/microsoft-graph-core`, or edit your `composer.json` file:
```
{
    "require": {
        "microsoft/microsoft-graph-core": "^2.1.1"
    }
}
```
## Get started with Microsoft Graph

### 1. Register your application

Register your application to use the Microsoft Graph API by following the steps at [Register an an application with the Microsoft Identity platform](https://aka.ms/registerApplication).

### 2. Authenticate with the Microsoft Graph service

The Microsoft Graph Core SDK for PHP does not include any default authentication implementations. The [`thephpleague/oauth2-client`](https://github.com/thephpleague/oauth2-client) library will handle the OAuth2 flow for you and provide a usable token for querying the Graph.

To authenticate as an application, please see [this guide](https://docs.microsoft.com/en-us/graph/auth-v2-service?context=graph%2Fapi%2F1.0&view=graph-rest-1.0) to configure the right permissions.

You can use the [Guzzle HTTP client](http://docs.guzzlephp.org/en/stable/), which comes preinstalled with this library, to get an access token like this:
```php

$tokenRequestContext = new ClientCredentialContext(
    'tenantId',
    'clientId',
    'clientSecret'
);
// requests using https://graph.microsoft.com/.default scopes by default
$tokenProvider = new GraphPhpLeagueAccessTokenProvider($tokenRequestContext);
$token = $tokenProvider->getAuthorizationTokenAsync(GraphConstants::REST_ENDPOINT)->wait();
```

### 3. Create a Guzzle HTTP client object
You can create a Guzzle HTTP client object pre-configured for use with the Graph API using our `GraphClientFactory`. The `GraphClientFactory`
sets some Guzzle config defaults such as connection and request timeouts, and the `base_uri` to your preferred [National Cloud endpoint](https://docs.microsoft.com/en-us/graph/deployments#microsoft-graph-and-graph-explorer-service-root-endpoints).

In the near future, the `GraphClientFactory` will provide some default middleware to use with the Graph API such as retry handlers.

```php
use Microsoft\Graph\Core\Core\Http\GraphClientFactory;

$guzzleConfig = [
    // your preferred guzzle config
];

$httpClient = GraphClientFactory::setClientConfig($guzzleConfig)::create();

```

### 4. Call Microsoft Graph using the v1.0 endpoint

The following is an example that shows how to call Microsoft Graph.

```php
use Microsoft\Graph\Core\Core\Http\GraphClientFactory;

class UsageExample
{
    public function run()
    {
        $accessToken = 'xxx';

        $config = [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken
            ]
        ];

        $httpClient = GraphClientFactory::setClientConfig($config)::create();
        $response = $httpClient->get("/v1.0/me");
        $currentUser = json_decode($response->getBody());

        echo "Hello, I am {$currentUser['givenName']}";
    }
}
```

We provide Microsoft Graph models for easy serialization and deserialization.

If you would like to leverage the models we provide, please take a look at the [Microsoft Graph PHP SDK](https://packagist.org/packages/microsoft/microsoft-graph) and for
beta models - the [Microsoft Graph Beta PHP SDK](https://packagist.org/packages/microsoft/microsoft-graph-beta).

## Documentation and resources

* [Microsoft Graph website](https://aka.ms/graph)

## Develop

### Run Tests

Run
 ```shell
vendor/bin/phpunit
```
from the base directory.

#### Debug tests on Windows

This SDK has an XDebug run configuration that attaches the debugger to VS Code so that you can debug tests.

1. Install the [PHP Debug](https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug) extension into Visual Studio Code.
2. From the root of this repo, using PowerShell, run `php .\tests\GetPhpInfo.php | clip` from the repo root. This will copy PHP configuration information into the clipboard which we will use in the next step.
3. Paste your clipboard into the [XDebug Installation Wizard](https://xdebug.org/wizard) and select **Analyse my phpinfo() output**.
4. Follow the generated instructions for installing XDebug. Note that the `/ext` directory is located in your PHP directory.
5. Add the following info to your php.ini file:

```
[XDebug]
xdebug.remote_enable = 1
xdebug.remote_autostart = 1
```

Now you can hit a Visual Studio Code breakpoint in a test. Try this:

1. Add a breakpoint to `testCreateWithConfigCreatesClient` in *.\tests\Http\GraphClientFactoryTest.php*.
2. Run the **Listen for XDebug** configuration in VS Code.
3. Run `.\vendor\bin\phpunit --filter testCreateWithConfigCreatesClient` from the PowerShell terminal to run the test and hit the breakpoint.

## Issues

View or log issues on the [Issues](https://github.com/microsoftgraph/msgraph-sdk-php-core/issues) tab in the repo.

## Contribute

Please read our [Contributing](https://github.com/microsoftgraph/msgraph-sdk-php-core/blob/master/CONTRIBUTING.md) guidelines carefully for advice on how to contribute to this repo.

## Copyright and license

Copyright (c) Microsoft Corporation. All Rights Reserved. Licensed under the MIT [license](LICENSE).

This project has adopted the [Microsoft Open Source Code of Conduct](https://opensource.microsoft.com/codeofconduct/). For more information see the [Code of Conduct FAQ](https://opensource.microsoft.com/codeofconduct/faq/) or contact [opencode@microsoft.com](mailto:opencode@microsoft.com) with any additional questions or comments.
