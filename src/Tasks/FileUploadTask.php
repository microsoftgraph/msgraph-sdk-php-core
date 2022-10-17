<?php
namespace Microsoft\Graph\Core\Tasks;

use Exception;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use InvalidArgumentException;
use Microsoft\Graph\Core\Models\LargFileTaskUploadCreateUploadSessionBody;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Psr\Http\Message\StreamInterface;
use SplQueue;

class FileUploadTask
{
    private LargeFileTaskUploadSession $uploadSession;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private int $uploadedChunks = 0;
    private int $chunks;
    private ?string $nextRange = null;
    private int $fileSize;
    private int $maxChunkSize;
    public function __construct(LargeFileTaskUploadSession $uploadSession, RequestAdapter $adapter, StreamInterface $stream, int $maxChunkSize = 5 * 1024 * 1024){
        $this->uploadSession = $uploadSession;
        $this->adapter = $adapter;
        $this->stream = $stream;
        $this->fileSize = $stream->getSize();
        $this->maxChunkSize = $maxChunkSize;
        $this->chunks = (int)ceil($this->fileSize / $maxChunkSize);
    }

    /**
    * @return LargeFileTaskUploadSession
     */
    public function getUploadSession(): LargeFileTaskUploadSession {
        return $this->uploadSession;
    }

    /**
     * @throws \Exception
     */
    public static function createUploadSession(RequestAdapter $adapter, LargFileTaskUploadCreateUploadSessionBody $uploadSessionBody, string $url): Promise {
        $requestInformation = new RequestInformation();
        $requestInformation->setUri($url);
        $requestInformation->httpMethod = HttpMethod::POST;
        $requestInformation->setContentFromParsable($adapter, 'application/json', $uploadSessionBody);
        return $adapter->sendAsync($requestInformation, [LargeFileTaskUploadSession::class, 'createFromDiscriminatorValue']);
    }
    /**
     * @return RequestAdapter
     */
    public function getAdapter(): RequestAdapter {
        return $this->adapter;
    }

    /**
     * @return int
     */
    public function getChunks(): int {
        return $this->chunks;
    }

    /**
     * @return int
     */
    public function getUploadedChunks(): int {
        return $this->uploadedChunks;
    }

    /**
     * @throws Exception
     */
    public function upload(?callable $afterChunkUpload = null): void {
        $q = new SplQueue();

        $session = $this->nextChunk($this->stream);
        $q->enqueue($session);

        while(!$q->isEmpty()){
            /** @var Promise $front */
            $front = $q->dequeue();

            $front->then(function (LargeFileTaskUploadSession $session) use (&$q, $afterChunkUpload){
                $this->uploadedChunks++;
                $afterChunkUpload($this);
                $this->setNextRange($session->getNextExpectedRanges()[0]);
                $prom = $this->nextChunk($this->stream);
                $q->enqueue($prom);
            });
        }
    }

    /**
     * @param string|null $nextRange
     */
    private function setNextRange(?string $nextRange): void {
        $this->nextRange = $nextRange;
    }

    /**
     * @throws Exception
     */
    private function nextChunk(StreamInterface $file): Promise {
        $uploadUrl = $this->uploadSession->getUploadUrl();

        if (empty($uploadUrl)) {
            throw new InvalidArgumentException('The upload session URL must not be empty.');
        }
        $info = new RequestInformation();
        $info->setUri($uploadUrl);
        $info->httpMethod = HttpMethod::PUT;
        if (empty($this->nextRange)) {
            $nextRange = $this->nextRange($uploadUrl);
            $this->setNextRange($nextRange);
        }
        $rangeParts = explode('-', $this->nextRange);
        $start = intval($rangeParts[0]);
        $end = intval($rangeParts[1]);
        $file->rewind();
        if ($start === 0 && $end === 0) {
            $chunkData = $file->read($this->maxChunkSize);
            $end = min($this->maxChunkSize  - 1, $this->fileSize - 1);
        } else if ($start === 0){
            $chunkData = $file->read($end + 1);
        }
        else if ($end === 0){
            $file->seek($start);
            $chunkData = $file->read($this->maxChunkSize);
            $end = $start + $this->maxChunkSize + 1;
        } else {
            $file->seek($start);
            $end = min($end, $this->maxChunkSize + $start);
            $chunkData = $file->read($end - $start + 1);

        }
        $info->headers = array_merge($info->headers, ['Content-Range' => 'bytes '.($start).'-'.($end).'/'.$this->fileSize]);
        $info->headers = array_merge($info->headers, ['Content-Length' => strlen($chunkData)]);

        $info->setStreamContent(Utils::streamFor($chunkData));
        return $this->adapter->sendAsync($info, [LargeFileTaskUploadSession::class, 'createFromDiscriminatorValue']);
    }

    /**
     * @throws Exception
     */
    private function nextRange(string $url): string {
        $info = new RequestInformation();
        $info->httpMethod = HttpMethod::GET;
        $info->setUri($url);
        $response = $this->adapter->sendAsync($info, [LargeFileTaskUploadSession::class, 'createFromDiscriminatorValue']);

        $next = '';
        $response->then(function (LargeFileTaskUploadSession $session) use (&$next){
            $this->setNextRange($session->getNextExpectedRanges()[0]);
            $next = $this->nextRange;
        });

        if (empty($next)) {
            throw new InvalidArgumentException('No range!!!!');
        }

        return $next;
    }

    /**
     * @return int
     */
    public function getMaxChunkSize(): int {
        return $this->maxChunkSize;
    }

    /**
     * @return StreamInterface
     */
    public function getFile(): StreamInterface {
        return $this->stream;
    }

    /**
     * @throws \Exception
     */
    public function cancel(): Promise {
        $requestInformation = new RequestInformation();
        $requestInformation->httpMethod = HttpMethod::DELETE;
        $uploadUrl =  $this->uploadSession->getUploadUrl();

        if (empty($uploadUrl)) {
            throw new InvalidArgumentException('The upload session URL must not be empty.');
        }
        $requestInformation->setUri($uploadUrl);
        return $this->adapter->sendNoContentAsync($requestInformation)
                      ->then(function () {
                          $this->uploadSession->setIsCancelled(true);
                        },
                          function ($error) {
                             throw new Exception($error);
                     });
    }

}
