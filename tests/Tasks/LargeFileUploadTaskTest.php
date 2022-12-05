<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use DateTime;
use GuzzleHttp\Psr7\Stream;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Graph\Core\Models\LargeFileUploadCreateUploadSessionBody;
use Microsoft\Graph\Core\Tasks\LargeFileUploadTask;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SplQueue;

class LargeFileUploadTaskTest extends TestCase
{
    private LargeFileUploadTask $largeFileUploadTask;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private Promise $promise;
    private LargeFileTaskUploadSession $session;
    private LargeFileUploadCreateUploadSessionBody $mockBody;
    private SplQueue $mockQueue;
    private LargeFileTaskUploadSession $mockSession;
    protected function setUp(): void {
        $this->largeFileUploadTask = $this->createMock(LargeFileUploadTask::class);
        $this->adapter = $this->createMock(RequestAdapter::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->promise = $this->createMock(Promise::class);
        $this->mockBody = $this->createMock(LargeFileUploadCreateUploadSessionBody::class);
        $this->mockQueue = $this->createMock(SplQueue::class);
        $this->session = new LargeFileTaskUploadSession();
        $this->mockSession = $this->createMock(LargeFileTaskUploadSession::class);
        $this->session->setUploadUrl('https://upload.example.com/session/1');
        $this->mockSession->setUploadUrl('https://upload.example.com/session/1');

    }

    /**
     * @throws \Exception
     */
    public function testUpload(): void
    {
        $this->session->setExpirationDateTime(new DateTime('12-12-2090'));
        $this->session->setUploadUrl('https://upload.example.com/session/1');
        $this->session->setNextExpectedRanges(['0-100']);
        $this->stream = new Stream(fopen('php://memory', 'rb+'));
        $this->stream->write(str_repeat("10101", 21));
        /** @phpstan-ignore-next-line */
        $this->adapter->method('sendAsync')
            ->willReturn($this->promise);
        /** @phpstan-ignore-next-line */
        $this->largeFileUploadTask->method('nextChunk')
            ->willReturn($this->promise);
        /** @phpstan-ignore-next-line */
        $this->promise->method('then')
            ->willReturnCallback(function ($needed){
                if (!is_null($needed) && is_callable($needed)) {
                    call_user_func($needed, $this->session);
                }
            });
        /** @phpstan-ignore-next-line */
        $this->promise->method('wait')
            ->willReturn($this->session);
        $this->session->setNextExpectedRanges([]);

        $session = $this->promise->wait();
        $lfu     = new LargeFileUploadTask($session, $this->adapter, $this->stream);
        $lfu->upload();
        $this->assertEquals($this->session, $lfu->getUploadSession());
        $this->assertEmpty([]);
        $this->stream->close();
    }

    /**
     * @throws \Exception
     */
    public function testUploadWithExpiredSession(): void {

        $this->expectException(RuntimeException::class);
        $this->session->setExpirationDateTime(new DateTime('12-12-2020'));
        $this->session->setUploadUrl('https://upload.example.com/session/1');
        $this->stream = new Stream(fopen('php://memory', 'rb'));
        /** @phpstan-ignore-next-line */
        $this->promise->method('wait')
            ->willReturn($this->session);
        $session = $this->promise->wait();
        $lfu = new LargeFileUploadTask($session, $this->adapter, $this->stream);
        $lfu->upload();
    }

    /**
     * @throws \Exception
     */
    public function testUploadSessionCheckExpiry(): void {
        $this->session->setExpirationDateTime(new DateTime('12-12-2090'));
        $this->stream = new Stream(fopen('php://memory', 'rb'));
        $this->session->setUploadUrl('https://upload.example.com/session/1');
        /** @phpstan-ignore-next-line */
        $this->promise->method('wait')
            ->willReturn($this->session);
        $session = $this->promise->wait();
        $lfu = new LargeFileUploadTask($session, $this->adapter, $this->stream);
        $this->assertFalse($lfu->uploadSessionExpired($session));
    }

    /**
     * @throws \Exception
     */
    public function testCreateUploadSession(): void {
        /** @phpstan-ignore-next-line */
        $this->adapter->method('sendAsync')
            ->willReturn($this->promise);
        /** @phpstan-ignore-next-line */
        $this->promise->method('wait')
            ->willReturn($this->session);
        $session = LargeFileUploadTask::createUploadSession($this->adapter, $this->mockBody, '/session/createUploadSession')->wait();
        $this->assertEquals($this->session, $session);
    }

    public function testGetUploadSession(): void {
        $this->stream = new Stream(fopen('php://memory', 'rb'));
        $lfu = new LargeFileUploadTask($this->session, $this->adapter, $this->stream);
        $this->assertEquals($this->session, $lfu->getUploadSession());
    }

    public function testCancel(): void {
        $this->stream = new Stream(fopen('php://memory', 'rb+'));
        $this->stream->write(str_repeat("10101", 21));
        /** @phpstan-ignore-next-line */
        $this->adapter->method('sendNoContentAsync')
            ->willReturn($this->promise);
        /** @phpstan-ignore-next-line */
        $this->promise->method('then')
            ->willReturnCallback(function ($needed){
                if (!is_null($needed) && is_callable($needed)) {
                    call_user_func($needed, $this->session);
                }
                return new FulfilledPromise($this->session);
            });

        $lfu = new LargeFileUploadTask($this->session, $this->adapter, $this->stream);
        $this->assertFalse($this->session->getIsCancelled());
        $lfu->cancel();
        $this->assertTrue($this->session->getIsCancelled());
    }

    /**
     * @throws \Exception
     */
    public function testResume(): void {
        $this->stream = new Stream(fopen('php://memory', 'rb+'));
        $this->stream->write(str_repeat("10101", 21));
        $this->session->setNextExpectedRanges(['10-']);
        $this->session->setExpirationDateTime(new DateTime('12-12-2090'));
        $lfu = new LargeFileUploadTask($this->session, $this->adapter, $this->stream);
        $lfu->resume($this->session);
        $this->assertEquals('10-', $lfu->getNextRange());
    }
}
