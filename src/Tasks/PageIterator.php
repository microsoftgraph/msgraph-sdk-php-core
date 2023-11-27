<?php

namespace Microsoft\Graph\Core\Tasks;

use Exception;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use InvalidArgumentException;
use JsonException;
use Microsoft\Graph\Core\Models\PageResult;
use Microsoft\Kiota\Abstractions\HttpMethod;
use Microsoft\Kiota\Abstractions\RequestAdapter;
use Microsoft\Kiota\Abstractions\RequestHeaders;
use Microsoft\Kiota\Abstractions\RequestInformation;
use Microsoft\Kiota\Abstractions\RequestOption;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;

/**
 * @template T of Parsable
 */
class PageIterator
{
    private PageResult $currentPage;
    private RequestAdapter $requestAdapter;
    private bool $hasNext = false;
    private int $pauseIndex;
    /**
     * @var array{class-string<T>, string} $constructorCallable
    */
    private array $constructorCallable;
    /** @var RequestHeaders */
    private RequestHeaders $headers;
    /** @var array<RequestOption>|null  */
    private ?array $requestOptions = [];

    /**
     * @param Parsable|array<mixed>|object $response paged collection response
     * @param RequestAdapter $requestAdapter
     * @param array{class-string<T>,string}|null $constructorCallable
     * The method to construct a paged response object.
     * @throws JsonException
     */
    public function __construct($response, RequestAdapter $requestAdapter, ?array $constructorCallable = null)
    {
        $this->requestAdapter = $requestAdapter;
        if ($response instanceof Parsable && !$constructorCallable) {
            /** @var array{class-string<T>, string} */
            $constructorCallable = [get_class($response), 'createFromDiscriminatorValue'];
        } elseif ($constructorCallable === null) {
            $constructorCallable = [PageResult::class, 'createFromDiscriminatorValue'];
        }
        $this->constructorCallable = $constructorCallable;
        $this->pauseIndex = 0;
        $this->headers = new RequestHeaders();
        $page = self::convertToPage($response);

        if ($page !== null) {
            $this->currentPage = $page;
            $this->hasNext = !empty($page->getValue());
        }
    }

    /**
     * @param array<string, array<string>|string> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers->putAll($headers);
    }

    /**
     * @param array<string, RequestOption> $requestOptions
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
     * @param callable(T|array<mixed>|object): bool $callback The callback function
     * to apply on every entity. Pauses iteration if false is returned
     * @throws Exception
     */
    public function iterate(callable $callback): void
    {
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
    public function next(): ?PageResult
    {
        if (empty($this->currentPage->getOdataNextLink())) {
            return null;
        }

        $response = $this->fetchNextPage();
        $result = $response->wait();
        return self::convertToPage($result);
    }

    /**
     * @param object|array<mixed>|Parsable|null $response
     * @return PageResult|null
     * @throws JsonException
     */
    public static function convertToPage($response): ?PageResult
    {
        $page = new PageResult();
        if ($response === null) {
            throw new InvalidArgumentException('$response cannot be null');
        }

        $value = null;
        if (is_array($response)) {
            $value = $response['value'] ?? [];
        } elseif ($response instanceof Parsable &&
            method_exists($response, 'getValue')) {
            $value = $response->getValue();
        } elseif (is_object($response)) {
            $value = property_exists($response, 'value') ? $response->value : [];
        }

        if ($value === null) {
            throw new InvalidArgumentException('The response does not contain a value.');
        }

        $parsablePage =  ($response instanceof Parsable) ? $response : json_decode(json_encode($response, JSON_THROW_ON_ERROR), true);
        if (is_array($parsablePage)) {
            $page->setOdataNextLink($parsablePage['@odata.nextLink'] ?? '');
        } elseif ($parsablePage instanceof Parsable &&
            method_exists($parsablePage, 'getOdataNextLink')) {
            $page->setOdataNextLink($parsablePage->getOdataNextLink());
        }
        $page->setValue($value);
        return $page;
    }

    /**
     * @return Promise<T|null>
     */
    private function fetchNextPage(): Promise
    {
        $nextLink = $this->currentPage->getOdataNextLink();

        if ($nextLink === null) {
            return new RejectedPromise(new InvalidArgumentException('The response does not have a nextLink'));
        }

        if (!filter_var($nextLink, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Could not parse the nextLink url.');
        }

        $requestInfo = new RequestInformation();
        $requestInfo->httpMethod = HttpMethod::GET;
        $requestInfo->setUri($nextLink);
        $requestInfo->setHeaders($this->headers->getAll());
        if ($this->requestOptions !== null) {
            $requestInfo->addRequestOptions(...$this->requestOptions);
        }

        return $this->requestAdapter->sendAsync($requestInfo, $this->constructorCallable);
    }

    public function enumerate(?callable $callback): ?bool
    {
        $keepIterating = true;

        $pageItems = $this->currentPage->getValue();
        if (empty($pageItems)) {
            return false;
        }
        for ($i = $this->pauseIndex; $i < count($pageItems); $i++){
            $keepIterating = $callback !== null ? $callback($pageItems[$i]) : true;

             if (!$keepIterating) {
                 $this->pauseIndex = $i + 1;
                 break;
             }
        }
        return $keepIterating;
    }

    public function hasNext(): bool
    {
        return $this->hasNext;
    }
}
