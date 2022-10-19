<?php
namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadCreateUploadSessionBody implements Parsable, AdditionalDataHolder {
    private array $additionalData = [];
    /** @var LargeFileUploadAttachmentItem|LargeFileUploadDriveItemUploadableProperties|LargeFileUploadPrintDocumentUploadProperties $body */
    private $body;
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
        $writer->writeObjectValue($this->getBodyKey(), $this->body);
        $writer->writeAdditionalData($this->additionalData);
    }

    public function driveItem(): LargeFileUploadDriveItemUploadableProperties {
        return $this->body = new LargeFileUploadDriveItemUploadableProperties();
    }

    public function printDocument(): LargeFileUploadPrintDocumentUploadProperties {
        return $this->body = new LargeFileUploadPrintDocumentUploadProperties();
    }

    public function attachmentItem(): LargeFileUploadAttachmentItem {
        return $this->body = new LargeFileUploadAttachmentItem();
    }

    /**
     * @param LargeFileUploadAttachmentItem|LargeFileUploadDriveItemUploadableProperties|LargeFileUploadPrintDocumentUploadProperties $body
     */
    public function setBody($body): void {
        $this->body = $body;
    }

    /**
     * @return LargeFileUploadAttachmentItem|LargeFileUploadDriveItemUploadableProperties|LargeFileUploadPrintDocumentUploadProperties
     */
    public function getBody() {
        return $this->body;
    }

    private function getBodyKey(): ?string {
        $map = [
            LargeFileUploadAttachmentItem::class => 'AttachmentItem',
            LargeFileUploadPrintDocumentUploadProperties::class => 'properties',
            LargeFileUploadDriveItemUploadableProperties::class => 'item'
        ];
        return $map[get_class($this->body)] ?? null;
    }
}
