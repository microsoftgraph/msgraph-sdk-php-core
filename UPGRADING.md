# Microsoft Graph PHP SDK Upgrade Guide

This guide highlights backward compatibility breaking changes introduced during major upgrades.


## 1.x to 2.0

Version `2.0` highlights:
- Support for National Clouds
- Changes in creating a Graph client
- Changes in configuring your HTTP client (including support for PSR-18 and HTTPlug's HttpAsyncClient implementations)
- Introducing standardised Graph exception types `GraphClientException` and `GraphServiceException` as more specific `GraphException` types
- Deprecates support for Guzzle `^6.0`
- Strongly typed method parameters and return type declarations where possible
- Psr compliance & other standardisation efforts in request classes and methods
    - Throwing `Psr\Http\Client\ClientExceptionInterface` instead of `\GuzzleHttp\Exception\GuzzleException` in request methods
    - Accepting and returning `Psr\Http\Message\StreamInterface` request bodies instead of `GuzzleHttp\Psr7\Stream`
    - Allow overwriting the default Guzzle client with `Psr\Http\Client\ClientExceptionInterface` for synchronous requests
    - Allow overwriting the default Guzzle client with HTTPlug's `Http\Client\HttpAsyncClient` for asynchronous requests
- Introduces a `PageIterator` that pages through a collection response while running a custom callback function against each entity.
It automatically fetches the nextPage until the end of the collection and allows you to pause and resume processing.

### Support for National Clouds
We have introduced `NationalCloud` containing Microsoft Graph API endpoint constants to enable you to easily
set base URLs and in future authenticate against the various supported National Clouds


### Creating a Graph client
- Version 2 deprecates setting HTTP-specific config via methods e.g. `setProxyPort()`
- Deprecates `setBaseUrl()` and `setApiVersion()` in favour of passing these into the constructor.
The National Cloud set will be used as the base URL.
- By default, the SDK will create a Guzzle HTTP client using our default config.
The HTTP client can be customised as shown in the next section


```php
$graphClient = new Graph(); // uses https://graph.microsoft.com as base URL and a Guzzle client as defaults
$response = $graphClient->setAccessToken("abc")
                        ->setReturnType(Model\User::class)
                        ->createRequest("GET", "/me")
                        ->execute();
```


### Configuring HTTP clients for use with the Graph API
We now support use of any HTTP client library that implements PSR-18 and HTTPlug's HttpAsyncClient interfaces.
In addition, we provide a `HttpClientInterface` that you can implement with your HTTP client library of choice to support both sync and async calls.

```php
$graphClient = new Graph(NationalCloud::GLOBAL); // creates & uses a default Guzzle client under the hood
```

#### 1. Custom configure a Guzzle client using the `HttpClientFactory`
------------------------------------------------------------------------
  To configure a Guzzle client to use with the SDK
  ```php
  $config = []; // your desired Guzzle client config
  $httpClient = HttpClientFactory::clientConfig($config)::createAdapter();
  $graphClient = new Graph(NationalCloud::GLOBAL, $httpClient);
  ```

  If you'd like to use the raw Guzzle client directly
  ```php
  $config = [
  // custom request options
  ];
  $guzzleClient = HttpClientFactory::clientConfig($config)::create();
  $response = $guzzleClient->get("/users/me");
  ```

  We would have loved to allow you to pass your guzzle client directly to the HttpClientFactory, however we would not be able to attach our recommended configs since Guzzle's `getConfig()` method is set to be deprecated in Guzzle 8.


#### 2. Configure any other HTTP client
----------------------------------------
Implement the `Microsoft\Graph\Http\HttpClientInterface` and pass your implementation to the `Graph` constructor

#### 3. Overwrite the HTTP client while making synchronous requests
--------------------------------------------------------------------
The SDK supports use of any PSR-18 compliant client for synchronous requests

```php
$customPsr18Client = new Psr18Client();
$graphClient = new Graph();
$response = $graphClient->setAccessToken("abc)
                        ->createRequest("GET", "/user/id")
                        ->execute($customPsr18Client); // overwrites the default Guzzle client created by Graph()
```

#### 4. Overwrite the HTTP client while making asynchronous requests
----------------------------------------------------------------------
The SDK supports using any HTTPlug HttpAsyncClient implementation for asynchronous requests
```php
$customClient = new HttpAsyncClientImpl();
$graphClient = new Graph();
$response = $graphClient->setAccessToken("abc")
                        ->createRequest("GET", "/user/id")
                        ->executeAsync($customClient); // overwrite the default Guzzle client created by Graph()
```


### Introducing the `GraphClientException`
This will be the exception type thrown going forward with regard to Graph client and GraphRequest configuration issues


### Introducing the `GraphServiceException`
This is the new standard exception to be thrown for `4xx` and `5xx` responses from the Graph. The exception contains the error payload returned by the Graph API via `getError()`.

### `GraphRequest` changes

#### 1. Deprecated functionality
---------------------------------
- Deprecates Guzzle-specific config and methods. We recommend using `HttpClientFactory` to configure your client:
  - Deprecated `setHttpErrors()`, `setTimeout()`
  - Deprecated `proxyPort` and `proxyVerifySSL`
- `$headers` and `$requestBody` are no longer `protected` attributes. Now `private`.
- Deprecates some getters: `getBaseUrl()`, `getApiVersion()`, `getReturnsStream()`


#### 2. Setting return type
----------------------------
- `setReturnType()` throws a `GraphClientException` if the return class passed is invalid.
- Deprecates setting return type to `GuzzleHttp\Psr7\Stream` in favour of `Psr\Http\Message\StreamInterface` to get a stream returned. This is because of our efforts
to make the SDK PSR compliant.


#### 3. Headers
----------------
- `getHeaders()` now returns `array<string, string[]` instead of previous `array<string, string>`
- `addHeaders()` also supports passing `array<string, string|string[]>`
- `addHeaders()` throws a `GraphClientException` if you attempt to overwrite the SDK Version header
- Extra layer of security by preventing sending your authorization tokens to non-Graph endpoints.

#### 4. Request Body
----------------------
- Supports passing any PSR-7 `StreamInterface` implementation to `attachBody()`


#### 5. Making Requests
-------------------------
- `execute()`, `download()` and `upload()` all accept any PSR-18 `ClientInterface` implementation to overwrite the SDK's default Guzzle client
- `execute()` now throws PSR-18 `ClientExceptionInterface` as opposed to `\GuzzleHttp\Exception\GuzzleException`
- `executeAsync()` now returns a HTTPlug `Http\Promise\Promise` instead of the previous Guzzle `GuzzleHttp\Promise\PromiseInterface`
- `executeAsync()` fails with a PSR-18 `ClientExceptionInterface` for HTTP client issues or `GraphServiceException` for 4xx/5xx response
- `download()` throws a `Psr\Http\Client\ClientExceptionInterface` as opposed to the previous `GuzzleHttp\Exception\GuzzleException`
- `download()` and `upload()` now throw a `GraphClientException` if the SDK is unable to open the file path given and read/write to it.


#### 6. Handling responses
----------------------------
- For `4xx` and `5xx` responses, the SDK will throw a `GraphServiceException` which contains the error payload.
- The status code is now an `int` from the previous `string` i.e. `getStatus()`

### `GraphCollectionRequest` changes
- Executing `count()` requests now throws PSR-18 `ClientExceptionInterface` in case of any HTTP related issues & a `GraphClientException` if the `@odata.count` does not exist in the payload
- `setPageSize()` throws a `GraphClientException` from the previous `GraphException`
- `getPage()` throws `Psr\Http\Client\ClientExceptionInterface` from previous `GuzzleHttp\Exception\GuzzleException`
- `getPage()` has been aligned to `execute()` to return a `GraphResponse` object if no return type is specified from previous JSON-decoded payload array.
  You can call `getBody()` on the `GraphResponse` returned to get the JSON-decoded array. If a return type is specified, `getPage()`
  still returns the deserialized response body.
- makes `setPageCallInfo()` and `processPageCallReturn()` `private` as these methods provide low level implementation detail
- See `GraphRequest` changes above as well.

### Introducing the `PageIterator`
The `PageIterator` allows you to now easily process each entity in a paged collection response without having to fetch each page manually via `getPage()`.
In addition, you control when to pause processing and resume from the last entity processed using the callback's return value.
If the callback returns `true`, iteration continues. If it returns `false` the iterator pauses.
The `PageIterator` returns a promise which resolves to `true` and throws exceptions should any be encountered.
Should your access token expire during iteration you can `setAccessToken()` then `resume()`.

```php
$callback = function () {}; // your callback
$iterator = $graphClient->createCollectionRequest("GET", "/me/messages")
                        ->pageIterator($callback);
$promise = $iterator->iterate();

# Resuming iteration
$promise = $iterator->resume();

# Setting a new access token in case it expires during iteration
$promise = $iterator->setAccessToken("abc")->resume();
```

