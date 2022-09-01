<?php

namespace Microsoft\Graph\Core\Tasks;

use Exception;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use InvalidArgumentException;
use Microsoft\Graph\Core\Models\PageResult;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\RequestOption;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;

class PageIterator
{
    private PageResult $currentPage;
    private RequestAdapter $requestAdapter;
    private bool $hasNext = true;
    private int $pauseIndex;
    /** @var array{string, string} $constructorFunc */
    private array $constructorCallable;
    private array $headers = [];
    /** @var array<RequestOption>|null  */
    private ?array $requestOptions = [];

    /**
     * @param $res
     * @param RequestAdapter $requestAdapter
     * @param array{string,string} $constructorCallable
     */
    public function __construct($res, RequestAdapter $requestAdapter, array $constructorCallable) {
        $this->requestAdapter = $requestAdapter;
        $this->constructorCallable = $constructorCallable;
        $this->pauseIndex = 0;
        $page = self::convertToPage($res);

        if ($page !== null) {
            $this->currentPage = $page;
        }
        $this->headers = [];
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @param array $requestOptions
     */
    public function setRequestOptions(array $requestOptions): void
    {
        $this->requestOptions = $requestOptions;
    }

    /**
     * @param int $pauseIndex
     */
    public function setPauseIndex(int $pauseIndex): void
    {
        $this->pauseIndex = $pauseIndex;
    }

    /**
     * @throws Exception
     */
    public function iterate(callable $callback): void {
        while(true) {
            $keepIterating = $this->enumerate($callback);

            if (!$keepIterating) {
                return;
            }
            $nextPage = $this->next();

            if (empty($nextPage)) {
                $this->hasNext = false;
                return;
            }
            $this->currentPage = $nextPage;
            $this->pauseIndex = 0;
        }
    }

    /**
     * @throws Exception
     */
    public function next(): ?PageResult {
        /** @var PageResult|null $page */
        $page = null;
        if (empty($this->currentPage->getNextLink())) {
            return null;
        }

        $response = $this->fetchNextPage();
        try {
            $result = $response->wait();
        } catch (Exception $exception){
            return null;
        }
        return self::convertToPage($result);
    }

    /**
     * @param $response
     * @return PageResult|null
     */
    public static function convertToPage($response): ?PageResult {
        $page = new PageResult();
        if ($response === null) {
            throw new InvalidArgumentException('$response cannot be null');
        }

        $value = $response->value;

        if ($value === null) {
            throw new InvalidArgumentException('');
        }

        $collected = [];

        for ($i = 0; $i < count($value); $i++) {
            $collected []= $value[$i];
        }
        $parsablePage =  json_decode(json_encode($response), true);
        $page->setNextLink($parsablePage['@odata.nextLink'] ?? '');
        $page->setValue($collected);
        return $page;
    }
    public function fetchNextPage(): ?Promise {
        /** @var Parsable $graphResponse */
        $graphResponse = null;

        $nextLink = $this->currentPage->getNextLink();

        if ($nextLink === null) {
            return new FulfilledPromise($graphResponse);
        }

        if (!filter_var($nextLink, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Could not parse the nextLink url.');
        }

        $requestInfo = new RequestInformation();
        $requestInfo->httpMethod = HttpMethod::GET;
        $requestInfo->setUri($nextLink);
        $requestInfo->headers = $this->headers;
        if ($this->requestOptions !== null) {
            $requestInfo->addRequestOptions(...$this->requestOptions);
        }

        return $this->requestAdapter->sendAsync($requestInfo, $this->constructorCallable);
    }

    public function enumerate(?callable $callback): ?bool {
        $keepIterating = true;

        $pageItems = $this->currentPage->getValue();
        if (empty($pageItems)) {
            return false;
        }
        for ($i = $this->pauseIndex; $i < count($pageItems); $i++){
            $keepIterating = $callback($pageItems[$i]);

             if (!$keepIterating) {
                 $this->pauseIndex = $i + 1;
                 break;
             }
        }
        return $keepIterating;
    }

    public function hasNext(): bool {
        return $this->hasNext;
    }
}
