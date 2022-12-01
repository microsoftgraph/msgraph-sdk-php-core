<?php
namespace Microsoft\Graph\Core\Tasks;

use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\Promise;
use InvalidArgumentException;
use Microsoft\Graph\Core\Errors\LargeFileUploadTaskErrors\Error404GetUploadStatusException;
use Microsoft\Graph\Core\Errors\LargeFileUploadTaskErrors\Error405GetUploadStatusException;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Graph\Core\Models\LargeFileUploadCreateUploadSessionBody;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use SplQueue;

class LargeFileUploadTask
{
    /** @var Parsable|LargeFileTaskUploadSession */
    private $uploadSession;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private int $uploadedChunks = 0;
    private int $chunks;
    private ?string $nextRange = null;
    private int $fileSize;
    private int $maxChunkSize;
    public function __construct(Parsable $uploadSession, RequestAdapter $adapter, StreamInterface $stream, int $maxChunkSize = 5 * 1024 * 1024){
        $this->uploadSession = $uploadSession;
        $this->adapter = $adapter;
        $this->stream = $stream;
        $this->fileSize = $stream->getSize();
        $this->maxChunkSize = $maxChunkSize;
        $this->chunks = (int)ceil($this->fileSize / $maxChunkSize);
    }

    /**
    * @return Parsable
     */
    public function getUploadSession(): Parsable {
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
    public function uploadSessionExpired(): bool {
        $now = new DateTime((new DateTime('now'))->format(DateTimeInterface::ATOM));

        if (!method_exists($this->uploadSession, 'getExpirationDateTime')) {
            throw new Exception();
        }
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
        $session = $this->nextChunk($this->stream, $start,max(0, min($this->maxChunkSize - 1,  $this->fileSize - 1)));
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
                if (!is_null($afterChunkUpload)) {
                    $afterChunkUpload($this);
                }
                $this->setNextRange($nextRange[0] . "-");
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
    public function nextChunk(StreamInterface $file, int $rangeStart = 0, int $rangeEnd = 0): Promise {

        if (!method_exists($this->uploadSession, 'getUploadUrl')) {
            throw new Exception();
        }
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

        if (!method_exists($this->uploadSession, 'getUploadUrl')) {
            throw new RuntimeException();
        }
        $uploadUrl = $this->uploadSession->getUploadUrl();
        if (empty($uploadUrl)) {
            throw new InvalidArgumentException('The upload session URL must not be empty.');
        }
        $requestInformation->setUri($uploadUrl);
        return $this->adapter->sendNoContentAsync($requestInformation)
                      ->then(function () {
                              if (method_exists($this->uploadSession, 'setIsCancelled')){
                                  $this->uploadSession->setIsCancelled(true);
                              }
                              else if (method_exists($this->uploadSession, 'setAdditionalData') && method_exists($this->uploadSession, 'getAdditionalData')){
                                  $current = $this->uploadSession->getAdditionalData();
                                  $new = array_merge($current, ['isCancelled' => true]);
                                  $this->uploadSession->setAdditionalData($new);
                              }
                        },
                          function ($error) {
                             throw new Exception($error);
                     });
    }

    /**
     * @param Parsable $parsable
     * @param string $property
     * @return bool
     */
    private function additionalDataContains(Parsable $parsable, string $property): bool  {
        if (!is_subclass_of($parsable, AdditionalDataHolder::class)) {
            throw new InvalidArgumentException('The object passed does not contains property '.$property.' and does not implement AdditionalDataHolder');
        }
        if (isset($parsable->getAdditionalData()[$property])) {
            return true;
        }
        return false;
    }

    /**
     * @param Parsable $parsable
     * @param string $getterName
     * @param string $propertyNameInAdditionalData
     * @return array{bool, mixed}
     */
    private function checkValueExists(Parsable $parsable, string $getterName, string $propertyNameInAdditionalData): array {
        if (method_exists($parsable, $getterName)) {
            return [true, $parsable->{$getterName}()];
        }
        if (is_subclass_of($parsable, AdditionalDataHolder::class) && $this->additionalDataContains($parsable, $propertyNameInAdditionalData)) {
            return [true, $parsable->getAdditionalData()[$propertyNameInAdditionalData]];
        }
        return [false, null];
    }

    /**
     * @throws Exception
     */
    public function resume(Parsable $uploadSession, ?callable $onRangeUploadComplete = null): void {
        if ($this->uploadSessionExpired()) {
            $this->uploadSession = $this->getUploadStatus($uploadSession)->wait();
            throw new RuntimeException('The upload session is expired.');
        }
        if (!method_exists($uploadSession, 'getNextExpectedRanges')) {
            throw new RuntimeException('The object passed does not contain a valid "nextExpectedRanges" property.');
        }

        $nextRanges = $uploadSession->getNextExpectedRanges();
        if (count($nextRanges) === 0) {
            throw new RuntimeException('No more bytes expected.');
        }
        $nextRange = $nextRanges[0];
        $this->nextRange = $nextRange;
        $this->uploadSession =  $uploadSession;
        $this->upload($onRangeUploadComplete);
    }

    private function getValidatedUploadUrl(Parsable $uploadSession): string {
        if (!method_exists($uploadSession, 'getUploadUrl')) {
            throw new RuntimeException('The upload session does not contain a valid upload url');
        }
        $result = $uploadSession->getUploadUrl();

        if ($result === null || trim($result) === '') {
            throw new RuntimeException('The upload URL cannot be empty.');
        }
        return $result;
    }

    private function getUploadStatus(Parsable $uploadSession): Promise {
        $info = new RequestInformation();
        $info->httpMethod = HttpMethod::GET;
        $url = $this->getValidatedUploadUrl($uploadSession);
        $info->setUri($url);
        $errorMappings = [
            '405' => [Error405GetUploadStatusException::class, 'createFromDiscriminatorValue'],
            '404' => [Error404GetUploadStatusException::class, 'createFromDiscriminatorValue']
        ];
        return $this->adapter->sendAsync($info, [LargeFileTaskUploadSession::class, 'createFromDiscriminatorValue'], null, $errorMappings);
    }

}