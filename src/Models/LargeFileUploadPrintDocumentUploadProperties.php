<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadPrintDocumentUploadProperties implements Parsable, AdditionalDataHolder
{
    private array $additionalData = [];
    private int $size = 0;
    private ?string $contentType = null;
    private ?string $documentName = null;
    /**
     * @inheritDoc
     */

    /**
     * @param int $size
     */
    public function setSize(int $size): void {
        $this->size = $size;
    }

    /**
     * @param string|null $contentType
     */
    public function setContentType(?string $contentType): void {
        $this->contentType = $contentType;
    }

    /**
     * @param string|null $documentName
     */
    public function setDocumentName(?string $documentName): void {
        $this->documentName = $documentName;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string {
        return $this->contentType;
    }

    /**
     * @return string|null
     */
    public function getDocumentName(): ?string {
        return $this->documentName;
    }

    /**
     * @return int
     */
    public function getSize(): int {
        return $this->size;
    }
    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDeserializers(): array {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function serialize(SerializationWriter $writer): void {
        $writer->writeStringValue('documentName', $this->documentName);
        $writer->writeStringValue('contentType', $this->contentType);
        $writer->writeIntegerValue('size', $this->size);
        $writer->writeAdditionalData($this->additionalData);
    }
}
