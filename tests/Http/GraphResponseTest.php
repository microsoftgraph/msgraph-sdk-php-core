<?php
use PHPUnit\Framework\TestCase;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Http\GraphRequest;
use Microsoft\Graph\Http\GraphResponse;
use Microsoft\Graph\Exception\GraphException;

class GraphResponseTest extends TestCase
{
    public $client;
    public $request;
    public $response;
    public $responseBody;

    public function setUp(): void
    {
        $this->responseBody = array('body' => 'content', 'displayName' => 'Bob Barker');

        $body = json_encode($this->responseBody);
        $multiBody = json_encode(array('value' => array('1' => array('givenName' => 'Bob'), '2' => array('givenName' => 'Drew'))));
        $valueBody = json_encode(array('value' => 'Bob Barker'));
        $emptyMultiBody = json_encode(array('value' => array()));

        $mock = new GuzzleHttp\Handler\MockHandler([
            new GuzzleHttp\Psr7\Response(200, ['foo' => 'bar'], $body),
            new GuzzleHttp\Psr7\Response(200, ['foo' => 'bar'], $body),
            new GuzzleHttp\Psr7\Response(200, ['foo' => 'bar'], $multiBody),
            new GuzzleHttp\Psr7\Response(200, ['foo' => 'bar'], $valueBody),
            new GuzzleHttp\Psr7\Response(200, ['foo' => 'bar'], $emptyMultiBody),
        ]);
        $handler = GuzzleHttp\HandlerStack::create($mock);
        $this->client = new GuzzleHttp\Client(['handler' => $handler]);

        $this->request = new GraphRequest("GET", "/endpoint", "token", "baseUrl", "version");
        $this->response = new GraphResponse($this->request, "{response}", "200", ["foo" => "bar"]);
    }

    public function testGetResponseHeaders()
    {
        $response = $this->request->execute($this->client);
        $headers = $response->getHeaders();

        $this->assertEquals(["foo" => ["bar"]], $headers);
    }

    public function testGetNextLink()
    {
        $body = json_encode(array('@odata.nextLink' => 'https://url.com/resource?$top=4&skip=4'));
        $response = new GraphResponse($this->request, $body);

        $nextLink = $response->getNextLink();
        $this->assertEquals('https://url.com/resource?$top=4&skip=4', $nextLink);
    }

    public function testDecodeBody()
    {
        //Temporarily make decodeBody() public
        $reflectionMethod = new ReflectionMethod('Microsoft\Graph\Http\GraphResponse', '_decodeBody');
        $reflectionMethod->setAccessible(true);

        $response = new GraphResponse($this->request, json_encode($this->responseBody));
        $decodedBody = $reflectionMethod->invokeArgs($response, array());

        $this->assertEquals($this->responseBody, $decodedBody);
    }

    public function testDecodeEmptyBody()
    {
        //Temporarily make decodeBody() public
        $reflectionMethod = new ReflectionMethod('Microsoft\Graph\Http\GraphResponse', '_decodeBody');
        $reflectionMethod->setAccessible(true);

        $response = new GraphResponse($this->request);
        $decodedBody = $reflectionMethod->invokeArgs($response, array());

        $this->assertEquals(array(), $decodedBody);
    }

    public function testGetHeaders()
    {
        $headers = $this->response->getHeaders();
        $this->assertEquals(["foo" => "bar"], $headers);
    }

    public function testGetBody()
    {
        $response = $this->request->execute($this->client);
        $this->assertInstanceOf(GraphResponse::class, $response);

        $body = $response->getBody();
        $this->assertEquals($this->responseBody, $body);
    }

    public function testGetRawBody()
    {
        $response = $this->request->execute($this->client);

        $body = $response->getRawBody();
        $this->assertEquals(json_encode($this->responseBody), $body);
    }

    public function testGetStatus()
    {
        $response = $this->request->execute($this->client);

        $this->assertEquals('200', $response->getStatus());
    }

    public function testGetMultipleObjects()
    {
        $this->request->execute($this->client);
        $this->request->execute($this->client);
        $hosts = $this->request->execute($this->client);
        $hostsResponseArray = [0 => $hosts->getBody()['value']];
        $this->assertIsArray($hostsResponseArray);
        $this->assertSame(array_values($hostsResponseArray), $hostsResponseArray);
        $this->assertEquals(2, count($hostsResponseArray[0]));
        $this->assertEquals("Bob", $hostsResponseArray[0][1]['givenName']);
    }

    public function testGetZeroMultipleObjects()
    {
        $this->request->execute($this->client);
        $this->request->execute($this->client);
        $this->request->execute($this->client);
        $this->request->execute($this->client);
        $response = $this->request->execute($this->client);

        $this->assertSame(array(), $response->getBody()['value']);
    }
}
