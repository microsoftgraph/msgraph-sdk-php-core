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

class LargeFileUploadTask
{
    /** @var Parsable|LargeFileUploadSession */
    private $uploadSession;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private int $chunks;
    private ?string $nextRange = null;
    private int $fileSize;
    private int $maxChunkSize;

    /**
     * @var callable(array{int, int}): void | null $onChunkUploadComplete
     */
    private $onChunkUploadComplete = null;
    public function __construct(Parsable $uploadSession, RequestAdapter $adapter, StreamInterface $stream, int $maxChunkSize = 4 * 1024 * 1024){
        $this->uploadSession = $uploadSession;
        $this->adapter = $adapter;
        $this->stream = $stream;
        $this->fileSize = $stream->getSize() ?? 0;
        $this->maxChunkSize = $maxChunkSize;
        /** @var string[] $cleanedValue */
        $cleanedValue = $this->checkValueExists($uploadSession, 'getNextExpectedRange',
            ['nextExpectedRange', 'NextExpectedRange']);
        $this->nextRange = $cleanedValue[0];
        $this->chunks = (int)ceil($this->fileSize / $maxChunkSize);
    }

    /**
     * Get the upload session used for the upload task.
     * @return Parsable
     */
    public function getUploadSession(): Parsable {
        return $this->uploadSession;
    }

    /**
     * Creates an upload session given the URL.
     * The URL should not include the hostname since that is already included in the baseUrl for the requestAdapter.
     * @param RequestAdapter $adapter
     * @param Parsable&AdditionalDataHolder $requestBody
     * @param string $url
     * @return Promise<LargeFileUploadSession|null>
     */
    public static function createUploadSession(RequestAdapter $adapter, $requestBody, string $url): Promise {
        $requestInformation = new RequestInformation();
        $baseUrl = rtrim($adapter->getBaseUrl(), '/');
        $path = ltrim($url, '/');
        $newUrl = "$baseUrl/$path";
        $requestInformation->setUri($newUrl);
        $requestInformation->httpMethod = HttpMethod::POST;
        $requestInformation->setContentFromParsable($adapter, 'application/json', $requestBody);
        return $adapter->sendAsync($requestInformation, [LargeFileUploadSession::class, 'createFromDiscriminatorValue']);
    }
    /**
     * Get the current request adapter used for the upload task.
     * @return RequestAdapter
     */
    public function getAdapter(): RequestAdapter {
        return $this->adapter;
    }

    /**
     * Get the total number of chunks the file requires to fully upload.
     * @return int
     */
    public function getChunks(): int {
        return $this->chunks;
    }

    /**
     * Checks if the current upload session is expired.
     * @param Parsable|null $uploadSession
     * @return bool
     * @throws Exception
     */
    private function uploadSessionExpired(?Parsable $uploadSession): bool {
        $now = new DateTime((new DateTime('now'))->format(DateTimeInterface::ATOM));

        $validatedValue = $this->checkValueExists($uploadSession ?? $this->uploadSession, 'getExpirationDateTime', ['ExpirationDateTime', 'expirationDateTime']);
        if (!$validatedValue[0]) {
            throw new Exception('The upload session does not contain an expiry datetime.');
        }
        /** @var DateTime|null $expiry */
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
     * Perform the actual upload for the whole file in bits.
     * @param callable(array{int, int}): void | null $afterChunkUpload
     * @return Promise<LargeFileUploadSession|null>
     * @throws Exception
     */
    public function upload(?callable $afterChunkUpload = null): Promise {
        // Rewind at this point to take care of failures.
        $this->stream->rewind();
        if ($this->uploadSessionExpired($this->uploadSession)){
            throw new RuntimeException('The upload session is expired.');
        }

        $this->onChunkUploadComplete ??= $afterChunkUpload;
        $session = $this->nextChunk($this->stream, 0,max(0, min($this->maxChunkSize - 1,  $this->fileSize - 1)));
        $processNext = $session;
        /// The logic below is to be used to accurately determine the range uploaded
        /// even in scenarios where we are resuming existing upload sessions.
        $rangeParts = explode("-", $this->nextRange[0] ?? '0-');
        $end = min(intval($rangeParts[0]) + $this->maxChunkSize - 1, $this->fileSize);
        $uploadedRange = [$rangeParts[0], $end];
        while($this->chunks > 0){
            $session = $processNext;
            $promise = $session->then(
                function (?LargeFileUploadSession $lfuSession) use (&$processNext, &$uploadedRange){
                    if (is_null($lfuSession)) {
                        return $lfuSession;
                    }
                    $nextRange = $lfuSession->getNextExpectedRanges();
                    $oldUrl = $this->getValidatedUploadUrl($this->uploadSession);
                    $lfuSession->setUploadUrl($oldUrl);
                    if (!is_null($this->onChunkUploadComplete)) {
                        call_user_func($this->onChunkUploadComplete, $uploadedRange);
                    }
                    if (empty($nextRange)) {
                        return $lfuSession;
                    }
                    $rangeParts = explode("-", $nextRange[0]);
                    $end = min(intval($rangeParts[0]) + $this->maxChunkSize, $this->fileSize);
                    $uploadedRange = [$rangeParts[0], $end];
                    $this->setNextRange($nextRange[0] . "-");
                    $processNext = $this->nextChunk($this->stream);
                    return $lfuSession;
            }, function ($error) {
                throw $error;
            });
            if ($promise !== null) {
                $promise->wait();
            }
            $this->chunks--;
        }
        return $session;
    }

    /**
     * @param string|null $nextRange
     */
    private function setNextRange(?string $nextRange): void {
        $this->nextRange = $nextRange;
    }

    /**
     * Upload the next chunk of file.
     * @return Promise<LargeFileUploadSession|null>
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
        $rangeParts = explode('-', ($this->nextRange ?? '-'));
        $start = intval($rangeParts[0]);
        $end = intval($rangeParts[1] ?? 0);
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
        $info->setHeaders(array_merge($info->getHeaders()->getAll(), ['Content-Range' => 'bytes '.($start).'-'.($end).'/'.$this->fileSize]));
        $info->setHeaders(array_merge($info->getHeaders()->getAll(), ['Content-Length' => (string) strlen($chunkData)]));

        $info->setStreamContent(Utils::streamFor($chunkData));
        return $this->adapter->sendAsync($info, [LargeFileUploadSession::class, 'createFromDiscriminatorValue']);
    }

    /**
     * Get the file stream.
     * @return StreamInterface
     */
    public function getFile(): StreamInterface {
        return $this->stream;
    }

    /**
     * Cancel an existing upload session from the File upload task.
     * @return Promise<LargeFileUploadSession|Parsable>
     * @throws Exception
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
     * Resumes an upload task.
     * @return Promise<LargeFileUploadSession|null>
     * @throws Exception
     */
    public function resume(): Promise {
        if ($this->uploadSessionExpired($this->uploadSession)) {
            throw new RuntimeException('The upload session is expired.');
        }
        /** @var array{bool,mixed} $validatedValue */
        $validatedValue = $this->checkValueExists($this->uploadSession, 'getNextExpectedRanges', ['NextExpectedRanges', 'nextExpectedRanges']);
        if (!$validatedValue[0]) {
            throw new RuntimeException('The object passed does not contain a valid "nextExpectedRanges" property.');
        }
        /** @var string[] $nextRanges */
        $nextRanges = $validatedValue[1];
        if (count($nextRanges) === 0) {
            throw new RuntimeException('No more bytes expected.');
        }
        $nextRange = $nextRanges[0];
        $this->nextRange = $nextRange;
        return $this->upload();
    }

    /**
     * Validates the URL and returns it if it is valid otherwise throw an exception.
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
     * Get the next range required by the API.
     * @return string|null
     */
    public function getNextRange(): ?string {
        return $this->nextRange;
    }

    /**
     * Get the filesize of the file being uploaded.
     * @return int
     */
    public function getFileSize(): int {
        return $this->fileSize;
    }
}
