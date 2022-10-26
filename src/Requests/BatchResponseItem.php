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
     * @var string Matches corresponding request ID
     */
    private string $id;

    /**
     * @var string
     */
    private string $atomicityGroup;

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var array
     */
    private array $headers = [];

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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAtomicityGroup(): string
    {
        return $this->atomicityGroup;
    }

    /**
     * @param string $atomicityGroup
     */
    public function setAtomicityGroup(string $atomicityGroup): void
    {
        $this->atomicityGroup = $atomicityGroup;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
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
}
