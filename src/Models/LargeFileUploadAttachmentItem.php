<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadAttachmentItem implements Parsable, AdditionalDataHolder
{
    private ?LargeFileUploadAttachmentType $attachmentType = null;
    private ?string $contentId = null;
    private ?string $contentType = null;
    private ?string $name = null;
    private bool $isInline = true;
    private int $size = 0;
    private array $additionalData = [];
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
        $writer->writeStringValue('name', $this->name);
        $writer->writeStringValue('contentId', $this->contentId);
        $writer->writeStringValue('contentType', $this->contentType);
        $writer->writeEnumValue('attachmentType', $this->attachmentType);
        $writer->writeBooleanValue('isInline', $this->isInline);
        $writer->writeIntegerValue('size', $this->size);
        $writer->writeAdditionalData($this->additionalData);
    }

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
     * @param string|null $name
     */
    public function setName(?string $name): void {
        $this->name = $name;
    }

    /**
     * @param LargeFileUploadAttachmentType|null $attachmentType
     */
    public function setAttachmentType(?LargeFileUploadAttachmentType $attachmentType): void {
        $this->attachmentType = $attachmentType;
    }

    /**
     * @param string|null $contentId
     */
    public function setContentId(?string $contentId): void {
        $this->contentId = $contentId;
    }

    /**
     * @param string|null $contentType
     */
    public function setContentType(?string $contentType): void {
        $this->contentType = $contentType;
    }

    /**
     * @param bool $isInline
     */
    public function setIsInline(bool $isInline): void {
        $this->isInline = $isInline;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void {
        $this->size = $size;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @return LargeFileUploadAttachmentType|null
     */
    public function getAttachmentType(): ?LargeFileUploadAttachmentType {
        return $this->attachmentType;
    }

    /**
     * @return string|null
     */
    public function getContentId(): ?string {
        return $this->contentId;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string {
        return $this->contentType;
    }

    /**
     * @return int
     */
    public function getSize(): int {
        return $this->size;
    }
}
