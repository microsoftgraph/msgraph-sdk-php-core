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
