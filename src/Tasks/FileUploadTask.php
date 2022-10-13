<?php
namespace Microsoft\Graph\Core\Tasks;

use Exception;
use InvalidArgumentException;
use Microsoft\Graph\Core\Models\LargFileTaskUploadCreateUploadSessionBody;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Graph\Core\Models\LargeFileTaskUploadSession;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Psr\Http\Message\StreamInterface;

class FileUploadTask
{
    private LargeFileTaskUploadSession $uploadSession;
    private RequestAdapter $adapter;
    private StreamInterface $stream;
    private int $maxChunkSize;
    public function __construct(LargeFileTaskUploadSession $uploadSession, RequestAdapter $adapter, StreamInterface $stream, int $maxChunkSize = 5 * 1024 * 1024){
        $this->uploadSession = $uploadSession;
        $this->adapter = $adapter;
        $this->stream = $stream;
        $this->maxChunkSize = $maxChunkSize;
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
    public static function createUploadSession(RequestAdapter $adapter, LargFileTaskUploadCreateUploadSessionBody $uploadSessionBody, string $url): LargeFileTaskUploadSession {
        $requestInformation = new RequestInformation();
        $requestInformation->setUri($url);
        $requestInformation->httpMethod = HttpMethod::POST;
        $requestInformation->setContentFromParsable($adapter, 'application/json', $uploadSessionBody);
        return $adapter->sendAsync($requestInformation, [LargeFileTaskUploadSession::class, 'createFromDiscriminatorValue'])->wait();
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
    public function cancel(): void {
        $requestInformation = new RequestInformation();
        $requestInformation->httpMethod = HttpMethod::DELETE;
        $uploadUrl =  $this->uploadSession->getUploadUrl();

        if (empty($uploadUrl)) {
            throw new InvalidArgumentException('The upload session URL must not be empty.');
        }
        $requestInformation->setUri($uploadUrl);
        $this->adapter->sendNoContentAsync($requestInformation)
                      ->then(function () {
                          $this->uploadSession->setIsCancelled(true);
                        },
                          function ($error) {
                             throw new Exception($error);
                     })->wait();
    }

}
