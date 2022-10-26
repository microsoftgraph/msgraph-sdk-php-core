<?php
namespace Microsoft\Graph\Core\Tasks;

use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\Promise;
use InvalidArgumentException;
use Microsoft\Graph\Core\Models\LargeFileUploadCreateUploadSessionBody;
use Microsoft\Kiota\Abstractions\Authentication\AnonymousAuthenticationProvider;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Http\GuzzleRequestAdapter;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SplQueue;

class LargeFileUploadTask
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
    public static function createUploadSession(RequestAdapter $adapter, LargeFileUploadCreateUploadSessionBody $uploadSessionBody, string $url): Promise {
        $requestInformation = new RequestInformation();
        $baseUrl = rtrim($adapter->getBaseUrl(), '/');
        $path = ltrim($url, '/');

        $newUrl = "{$baseUrl}/$path";
        $requestInformation->setUri($newUrl);
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
    private function uploadSessionExpired(): bool {
        $now = new DateTime((new DateTime('now'))->format(DateTimeInterface::ATOM));
        $expiry = $this->uploadSession->getExpirationDateTime();

        if ($expiry === null){
            throw new InvalidArgumentException('The upload session does not contain a valid expiry date.');
        }
        $then = new DateTime($expiry->format(DateTimeInterface::ATOM));
        $interval = $now->diff($then);

        if ($interval->invert !== 0){
            return true;
        }
        return false;
    }
    /**
     * @throws Exception
     */
    public function upload(?callable $afterChunkUpload = null): void {

        if ($this->uploadSessionExpired()){
            throw new RuntimeException('The upload session is expired.');
        }
        $q = new SplQueue();

        $start = 0;
        $session = $this->nextChunk($this->stream, $start,max(0, min($this->maxChunkSize,  $this->fileSize - 1)));
        $q->enqueue($session);

        while(!$q->isEmpty()){
            /** @var Promise $front */
            $front = $q->dequeue();

            $front->then(function (LargeFileTaskUploadSession $session) use (&$q, $afterChunkUpload){
                $nextRange = $session->getNextExpectedRanges();
                if (empty($nextRange)) {
                    echo "Upload finished!!!!\n";
                    return;
                }
                $this->uploadedChunks++;
                $afterChunkUpload($this);
                $this->setNextRange($nextRange[0]."-");
                $nextChunkTask = $this->nextChunk($this->stream);
                $q->enqueue($nextChunkTask);
            }, function ($error) {
                throw $error;
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
    private function nextChunk(StreamInterface $file, int $rangeStart = 0, int $rangeEnd = 0): Promise {
        $uploadUrl = $this->uploadSession->getUploadUrl();

        if (empty($uploadUrl)) {
            throw new InvalidArgumentException('The upload session URL must not be empty.');
        }
        $info = new RequestInformation();
        $info->setUri($uploadUrl);
        $info->httpMethod = HttpMethod::PUT;
        if (empty($this->nextRange)) {
            $this->setNextRange($rangeStart.'-'.$rangeEnd);
        }
        $rangeParts = explode('-', $this->nextRange);
        $start = intval($rangeParts[0]);
        $end = intval($rangeParts[1] ?? 0);
        $file->rewind();
        if ($start === 0 && $end === 0) {
            $chunkData = $file->read($this->maxChunkSize);
            $end = min($this->maxChunkSize - 1, $this->fileSize - 1);
        } else if ($start === 0){
            $chunkData = $file->read($end + 1);
        }
        else if ($end === 0){
            $file->seek($start);
            $chunkData = $file->read($this->maxChunkSize);
            $end = $start + strlen($chunkData) - 1;
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
