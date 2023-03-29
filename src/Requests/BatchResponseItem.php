<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Requests;


use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Psr\Http\Message\StreamInterface;

class BatchResponseItem implements Parsable
{
    /**
     * @var string|null Matches corresponding request ID
     */
    private ?string $id = null;

    /**
     * @var string|null $atomicityGroup
     */
    private ?string $atomicityGroup = null;

    /**
     * @var int|null
     */
    private ?int $statusCode = null;

    /**
     * @var array<string, string>|null $headers
     */
    private ?array $headers = [];

    /**
     * @var StreamInterface|null
     */
    private ?StreamInterface $body = null;

    public function __construct() {}

    public function getFieldDeserializers(): array
    {
       return [
           'id' => fn (ParseNode $n) => $this->setId($n->getStringValue()),
           'atomicityGroup' => fn (ParseNode $n) => $this->setAtomicityGroup($n->getStringValue()),
           'status' => fn (ParseNode $n) => $this->setStatusCode($n->getIntegerValue()),
           /** @phpstan-ignore-next-line */
           'headers' => fn (ParseNode $n) => $this->setHeaders($n->getCollectionOfPrimitiveValues('string')),
           'body' => fn (ParseNode $n) => $this->setBody($n->getBinaryContent())
       ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('id', $this->getId());
        $writer->writeStringValue('atomicityGroup', $this->getAtomicityGroup());
        $writer->writeIntegerValue('statusCode', $this->getStatusCode());
        $writer->writeAnyValue('headers', $this->getHeaders());
        $writer->writeBinaryContent('body', $this->getBody());
    }

    public static function create(ParseNode $parseNode): BatchResponseItem
    {
        return new BatchResponseItem();
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string|null
     */
    public function getAtomicityGroup(): ?string
    {
        return $this->atomicityGroup;
    }

    /**
     * @param string|null $atomicityGroup
     */
    public function setAtomicityGroup(?string $atomicityGroup): void
    {
        $this->atomicityGroup = $atomicityGroup;
    }

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     */
    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array<string, string>|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @param array<string,string>|null $headers
     */
    public function setHeaders(?array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return StreamInterface|null
     */
    public function getBody(): ?StreamInterface
    {
        return $this->body;
    }

    /**
     * @param StreamInterface|null $body
     */
    public function setBody(?StreamInterface $body): void
    {
        $this->body = $body;
    }

    /**
     * Case-insensitively checks for Content-Type header key and returns value
     * @return string|null
     */
    public function getContentType(): ?string
    {
        if ($this->headers) {
            $headers = array_change_key_case($this->headers);
            return $headers['content-type'] ?? null;
        }
        return null;
    }
}
