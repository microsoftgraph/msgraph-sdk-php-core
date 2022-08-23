<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class PageIteratorTest extends TestCase
{
    private $mock;
    private $handlerStack;
    private $testClient;
    protected function setUp(): void {
        $this->mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], '{
    "@odata.context": "https://graph.microsoft.com/v1.0/$metadata#users",
    "value": [
        {
            "businessPhones": [],
            "displayName": "Conf Room Adams",
            "givenName": null,
            "jobTitle": null,
            "mail": "Adams@contoso.com",
            "mobilePhone": null,
            "officeLocation": null,
            "preferredLanguage": null,
            "surname": null,
            "userPrincipalName": "Adams@contoso.com",
            "id": "6ea91a8d-e32e-41a1-b7bd-d2d185eed0e0"
        },
        {
            "businessPhones": [
                "425-555-0100"
            ],
            "displayName": "MOD Administrator",
            "givenName": "MOD",
            "jobTitle": null,
            "mail": null,
            "mobilePhone": "425-555-0101",
            "officeLocation": null,
            "preferredLanguage": "en-US",
            "surname": "Administrator",
            "userPrincipalName": "admin@contoso.com",
            "id": "4562bcc8-c436-4f95-b7c0-4f8ce89dca5e"
        },
        {},
        {},
        {},
        {},
        {},
        {},
        {},
        {},
        {},
        {}
    ]
}
            '),
        ]);
        $this->handlerStack = HandlerStack::create($this->mock);
        $this->testClient = new Client(['handler' => $this->handlerStack]);
    }

    public function testHandlerCanWork(): void {
        $content = json_decode($this->testClient->get('/')->getBody()->getContents())->value;
        $this->assertEquals([new \stdClass()],$content);
    }
}
