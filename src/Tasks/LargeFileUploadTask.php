<?php
namespace Microsoft\Graph\Core\Tasks;

use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Http\Promise\Promise;
use InvalidArgumentException;
use Microsoft\Graph\Core\Models\LargeFileUploadSession;
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
    /** @var Parsable|LargeFileUploadSession */
    private $uploadSession;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private int $uploadedChunks = 0;
    private int $chunks;
    private ?string $nextRange = null;
    private int $fileSize;
    private int $maxChunkSize;
    private int $uploaded = 0;
    public function __construct(Parsable $uploadSession, RequestAdapter $adapter, StreamInterface $stream, int $maxChunkSize = 320 * 1024){
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
    public static function createUploadSession(RequestAdapter $adapter, Parsable $requestBody, string $url): Promise {
        $requestInformation = new RequestInformation();
        $baseUrl = rtrim($adapter->getBaseUrl(), '/');
        $path = ltrim($url, '/');

        $newUrl = "{$baseUrl}/$path";
        $requestInformation->setUri($newUrl);
        $requestInformation->httpMethod = HttpMethod::POST;
        $requestInformation->setContentFromParsable($adapter, 'application/json', $requestBody);
        return $adapter->sendAsync($requestInformation, [LargeFileUploadSession::class, 'createFromDiscriminatorValue']);
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
     * @param Parsable|null $uploadSession
     * @throws Exception
     */
    public function uploadSessionExpired(?Parsable $uploadSession): bool {
        $now = new DateTime((new DateTime('now'))->format(DateTimeInterface::ATOM));

        $validatedValue = $this->checkValueExists($uploadSession ?? $this->uploadSession, 'getExpirationDateTime', ['ExpirationDateTime', 'expirationDateTime']);
        if (!$validatedValue[0]) {
            throw new Exception('The upload session does not contain an expiry datetime.');
        }
        $expiry = $validatedValue[1];

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

        if ($this->uploadSessionExpired($this->uploadSession)){
            throw new RuntimeException('The upload session is expired.');
        }
        $q = new SplQueue();

        $start = 0;
        $session = $this->nextChunk($this->stream, $start,max(0, min($this->maxChunkSize - 1,  $this->fileSize - 1)));
        $q->enqueue($session);

        while(!$q->isEmpty()){
            /** @var Promise $front */
            $front = $q->dequeue();

            $front->then(function (LargeFileUploadSession $session) use (&$q, $afterChunkUpload){
                $nextRange = $session->getNextExpectedRanges();
                $this->uploaded = (int)explode('-', $nextRange[0] ?? ($this->fileSize.'-'))[0];
                if (empty($nextRange)) {
                    echo "Upload finished!!!!\n";
                    return $session;
                }
                $this->uploadedChunks++;
                if (!is_null($afterChunkUpload)) {
                    $afterChunkUpload($this);
                }
                $this->setNextRange($nextRange[0] . "-");
                $nextChunkTask = $this->nextChunk($this->stream);
                $q->enqueue($nextChunkTask);
                return $session;
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
        $uploadUrl = $this->getValidatedUploadUrl($this->uploadSession);

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
        return $this->adapter->sendAsync($info, [LargeFileUploadSession::class, 'createFromDiscriminatorValue']);
    }

    /**
     * @return StreamInterface
     */
    public function getFile(): StreamInterface {
        return $this->stream;
    }

    /**
     * @return Promise
     * @throws \Exception
     */
    public function cancel(): Promise {
        $requestInformation = new RequestInformation();
        $requestInformation->httpMethod = HttpMethod::DELETE;

        $uploadUrl = $this->getValidatedUploadUrl($this->uploadSession);

        $requestInformation->setUri($uploadUrl);
        return  $this->adapter->sendNoContentAsync($requestInformation)
                              ->then(function ($result) {
                                      if (method_exists($this->uploadSession, 'setIsCancelled')){
                                          $this->uploadSession->setIsCancelled(true);
                                      }
                                      else if (method_exists($this->uploadSession, 'setAdditionalData') && method_exists($this->uploadSession, 'getAdditionalData')){
                                          $current = $this->uploadSession->getAdditionalData();
                                          $new = array_merge($current, ['isCancelled' => true]);
                                          $this->uploadSession->setAdditionalData($new);
                                      }
                                      return $this->uploadSession;
                              });
    }

    /**
     * @param Parsable $parsable
     * @param array<string> $propertyCandidates
     * @return array{boolean,mixed}
     */
    private function additionalDataContains(Parsable $parsable, array $propertyCandidates): array  {
        if (!is_subclass_of($parsable, AdditionalDataHolder::class)) {
            throw new InvalidArgumentException('The object passed does not contain propert(y|ies) ['.implode(',',$propertyCandidates).'] and does not implement AdditionalDataHolder');
        }
        $additionalData = $parsable->getAdditionalData();
        foreach ($propertyCandidates as $propertyCandidate) {
            if (isset($additionalData[$propertyCandidate])) {
                return [true, $additionalData[$propertyCandidate]];
            }
        }
        return [false, null];
    }

    /**
     * @param Parsable $parsable
     * @param string $getterName
     * @param array<string> $propertyNamesInAdditionalData
     * @return array{bool, mixed}
     */
    private function checkValueExists(Parsable $parsable, string $getterName, array $propertyNamesInAdditionalData): array {
        $checkedAdditionalData = $this->additionalDataContains($parsable, $propertyNamesInAdditionalData);
        if (is_subclass_of($parsable, AdditionalDataHolder::class) && $checkedAdditionalData[0]) {
            return [true, $checkedAdditionalData[1]];
        }

        if (method_exists($parsable, $getterName)) {
            return [true, $parsable->{$getterName}()];
        }
        return [false, null];
    }

    /**
     * @throws Exception
     */
    public function resume(Parsable $uploadSession, ?callable $onRangeUploadComplete = null): void {
        if ($this->uploadSessionExpired($uploadSession)) {
            throw new RuntimeException('The upload session is expired.');
        }
        $validatedValue = $this->checkValueExists($uploadSession, 'getNextExpectedRanges', ['NextExpectedRanges', 'nextExpectedRanges']);
        if (!$validatedValue[0]) {
            throw new RuntimeException('The object passed does not contain a valid "nextExpectedRanges" property.');
        }

        $nextRanges = $validatedValue[1];
        if (count($nextRanges) === 0) {
            throw new RuntimeException('No more bytes expected.');
        }
        $nextRange = $nextRanges[0];
        $this->nextRange = $nextRange;
        $this->uploadSession =  $uploadSession;
        $this->upload($onRangeUploadComplete);
    }

    /**
     * @param Parsable $uploadSession
     * @return string
     */
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

    /**
     * @return string|null
     */
    public function getNextRange(): ?string {
        return $this->nextRange;
    }

    /**
     * @return int
     */
    public function getUploaded(): int {
        return $this->uploaded;
    }

    /**
     * @return int
     */
    public function getFileSize(): int {
        return $this->fileSize;
    }
}
