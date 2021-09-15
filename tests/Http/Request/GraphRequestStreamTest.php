<?php

namespace Microsoft\Graph\Test\Http\Request;

use Microsoft\Graph\Exception\GraphClientException;
use Microsoft\Graph\Exception\GraphServiceException;
use Microsoft\Graph\Test\Http\SampleGraphResponsePayload;
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

    public function testUploadThrowsExceptionForErrorResponse()
    {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient);
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

    public function testDownloadThrowsExceptionForErrorResponse()
    {
        $this->expectException(GraphServiceException::class);
        MockHttpClientResponseConfig::configureWithErrorPayload($this->mockHttpClient);
        $file = new VfsStreamFile('foo.txt');
        $this->rootDir->addChild($file);
        $this->defaultGraphRequest->download($file->url());
    }
}
