<?php

namespace Microsoft\Graph\Core\Core\Test\Http\Request;

use Microsoft\Graph\Core\Core\Exception\GraphClientException;
use Microsoft\Graph\Core\Core\Exception\GraphServiceException;
use Microsoft\Graph\Core\Core\Test\Http\SampleGraphResponsePayload;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

class GraphRequestStreamTest extends BaseGraphRequestTest
{
    private $rootDir;

    public function setUp(): void
    {
        $this->rootDir = vfsStream::setup('testDir');
        parent::setUp();
    }

    public function testUpload()
    {
        MockHttpClientResponseConfig::configureWithEmptyPayload($this->mockHttpClient);
        $file = vfsStream::newFile('foo.txt')
                            ->withContent("content")
                            ->at($this->rootDir);
        $this->defaultGraphRequest->upload($file->url());
        $this->assertEquals($this->defaultGraphRequest->getBody()->getContents(), $file->getContent());
    }

    public function testInvalidUpload()
    {
        $this->expectException(\RuntimeException::class);
        $file = new VfsStreamFile('foo.txt', 0000);
        $this->rootDir->addChild($file);
        $this->defaultGraphRequest->upload($file->url());
    }

    public function testUploadThrowsExceptionFor4xxResponse()
    {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $file = vfsStream::newFile('foo.txt')
            ->withContent("content")
            ->at($this->rootDir);
        $this->defaultGraphRequest->upload($file->url());
    }

    public function testUploadThrowsExceptionFor5xxResponse()
    {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $file = vfsStream::newFile('foo.txt')
            ->withContent("content")
            ->at($this->rootDir);
        $this->defaultGraphRequest->upload($file->url());
    }

    public function testDownload()
    {
        $file = new VfsStreamFile('foo.txt');
        $this->rootDir->addChild($file);

        MockHttpClientResponseConfig::configureWithStreamPayload($this->mockHttpClient);
        $this->defaultGraphRequest->download($file->url());
        $this->assertEquals(SampleGraphResponsePayload::STREAM_PAYLOAD()->getContents(), $file->getContent());
    }

    public function testInvalidDownload()
    {
        $this->expectException(\RuntimeException::class);
        $file = new VfsStreamFile('foo.txt', 0000);
        $this->rootDir->addChild($file);
        $this->defaultGraphRequest->download($file->url());
    }

    public function testDownloadThrowsExceptionFor4xxResponse()
    {
        $this->expectException(GraphClientException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 400);
        $file = new VfsStreamFile('foo.txt');
        $this->rootDir->addChild($file);
        $this->defaultGraphRequest->download($file->url());
    }

    public function testDownloadThrowsExceptionFor5xxResponse()
    {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient, 500);
        $file = new VfsStreamFile('foo.txt');
        $this->rootDir->addChild($file);
        $this->defaultGraphRequest->download($file->url());
    }
}
