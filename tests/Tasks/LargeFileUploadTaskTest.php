<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use GuzzleHttp\Psr7\Stream;
use Http\Promise\Promise;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Graph\Core\Tasks\LargeFileUploadTask;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class LargeFileUploadTaskTest extends TestCase
{
    private LargeFileUploadTask $largeFileUploadTask;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private Promise $promise;
    private LargeFileTaskUploadSession $session;
    protected function setUp(): void {
        $this->largeFileUploadTask = $this->createMock(LargeFileUploadTask::class);
        $this->adapter = $this->createMock(RequestAdapter::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->promise = $this->createMock(Promise::class);
        $this->session = new LargeFileTaskUploadSession();

    }

    public function testUpload()
    {

    }

    /**
     * @throws \Exception
     */
    public function testUploadWithExpiredSession(): void {

        $this->expectException(RuntimeException::class);
        $this->session->setExpirationDateTime(new \DateTime('12-12-2020'));
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
        $this->session->setExpirationDateTime(new \DateTime('12-12-2090'));
        $this->session->setUploadUrl('https://upload.example.com/session/1');
        $this->stream = new Stream(fopen('php://memory', 'rb'));
        /** @phpstan-ignore-next-line */
        $this->promise->method('wait')
            ->willReturn($this->session);
        $session = $this->promise->wait();
        $lfu = new LargeFileUploadTask($session, $this->adapter, $this->stream);
        $this->assertFalse($lfu->uploadSessionExpired());
    }

    public function testCreateUploadSession(): void {

    }

    public function testGetUploadSession(): void {

    }

    public function testCancel(): void {

    }

    public function testResume(): void {

    }
}
