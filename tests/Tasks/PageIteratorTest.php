<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\FulfilledPromise;
use Microsoft\Graph\Core\Tasks\PageIterator;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Http\GuzzleRequestAdapter;
use Microsoft\Kiota\Serialization\Json\JsonParseNode;
use Microsoft\Kiota\Serialization\Json\JsonParseNodeFactory;
use PHPUnit\Framework\TestCase;

class PageIteratorTest extends TestCase
{
    private $mock;
    private HandlerStack $handlerStack;
    private Client $testClient;
    private RequestAdapter $mockRequestAdapter;
    private RequestInformation $requestInfoMock;
    private $firstPageContent;

    /**
     * @throws GuzzleException
     */
    protected function setUp(): void {

        $this->mockRequestAdapter = $this->createMock(GuzzleRequestAdapter::class);
        $this->requestInfoMock = $this->createMock(RequestInformation::class);
        $data = Utils::streamFor('{
                "@odata.context": "https://graph.microsoft.com/v1.0/$metadata#users",
                "value": [
                    {
                        "businessPhones": [],
                        "displayName": "Conf Room Adams 2",
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
                        "displayName": "MOD Administrator 2",
                        "givenName": "MOD",
                        "jobTitle": null,
                        "mail": null,
                        "mobilePhone": "425-555-0101",
                        "officeLocation": null,
                        "preferredLanguage": "en-US",
                        "surname": "Administrator",
                        "userPrincipalName": "admin@contoso.com",
                        "id": "4562bcc8-c436-4f95-b7c0-4f8ce89dca5e"
                    }
                ]
            }
            ');
        $usersPage = new FulfilledPromise(json_decode($data->getContents()));
        $firstPageData = '{
                "@odata.context": "https://graph.microsoft.com/v1.0/$metadata#users",
                "@odata.nextLink": "https://graph.microsoft.com/v1.0/users?skip=2&page=10",
                "value": [
                    {
                        "businessPhones": [],
                        "displayName": "Conf Room Adams 1",
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
                        "displayName": "MOD Administrator 1",
                        "givenName": "MOD",
                        "jobTitle": null,
                        "mail": null,
                        "mobilePhone": "425-555-0101",
                        "officeLocation": null,
                        "preferredLanguage": "en-US",
                        "surname": "Administrator",
                        "userPrincipalName": "admin@contoso.com",
                        "id": "4562bcc8-c436-4f95-b7c0-4f8ce89dca5e"
                    }
                ]
            }
            ';
        $this->mockRequestAdapter->method('sendAsync')
            ->willReturn($usersPage);
        $this->mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $firstPageData),
        ]);
        $this->handlerStack = HandlerStack::create($this->mock);
        $this->testClient = new Client(['handler' => $this->handlerStack]);
        $this->firstPageContent = json_decode($this->testClient->get('/')->getBody()->getContents());
    }

    /**
     * @throws \Exception
     */
    public function testHandlerCanWork(): void {
        $pageIterator = new PageIterator($this->firstPageContent, $this->mockRequestAdapter, [UsersResponse::class, 'createFromDiscriminator']);
        $count = 0;
        $pageIterator->iterate(function ($value) use (&$count)  {
            $count++;
            return true;
        });
        $this->assertEquals(4, $count);
    }

    /**
     * @throws \Exception
     */
    public function testCanResumeIteration(): void{
        $pageIterator = new PageIterator($this->firstPageContent, $this->mockRequestAdapter, [UsersResponse::class, 'createFromDiscriminator']);
        $count = 0;
        $pageIterator->iterate(function ($value) use (&$count)  {
            $parseNode = new JsonParseNode($value);
            /** @var User $user */
            $user = $parseNode->getObjectValue([User::class, 'createFromDiscriminator']);
            $count++;
            return '6ea91a8d-e32e-41a1-b7bd-d2d185eed0e0' !== $user->getId();
        });
        $this->assertEquals(1, $count);
        $pageIterator->iterate(function ($value) use (&$count)  {
            $count++;
            return true;
        });
        $this->assertEquals(4, $count);
    }

    public function testCanPauseIteration(): void {
        $pageIterator = new PageIterator($this->firstPageContent, $this->mockRequestAdapter, [UsersResponse::class, 'createFromDiscriminator']);
        $count = 0;
        $pageIterator->iterate(function ($value) use (&$count)  {
            $parseNode = new JsonParseNode($value);
            /** @var User $user */
            $user = $parseNode->getObjectValue([User::class, 'createFromDiscriminator']);
            $count++;
            return '6ea91a8d-e32e-41a1-b7bd-d2d185eed0e0' !== $user->getId();
        });

        $this->assertEquals(1, $count);
    }

    /**
     * @throws \Exception
     */
    public function testCanDeserializeUsingCallback(): void {
        $pageIterator = new PageIterator($this->firstPageContent, $this->mockRequestAdapter, [UsersResponse::class, 'createFromDiscriminator']);
        $count = 0;
        $usersArray = [];
        $pageIterator->iterate(function ($value) use (&$count,&$usersArray)  {
            $parseNode = new JsonParseNode($value);
            /** @var User $user */
            $user = $parseNode->getObjectValue([User::class, 'createFromDiscriminator']);
            $usersArray []= $user;
            $count++;
            return true;
        });

        $this->assertInstanceOf(User::class, $usersArray[0]);
    }
}
