<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadDriveItemUploadableProperties implements Parsable, AdditionalDataHolder
{
    private ?string $name = null;
    private ?int $fileSize = null;
    private array $additionalData = [];
    private ?string $description = null;
    private ?LargeFileUploadConflictBehavior $conflictBehavior = null;

    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }
    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    public function serialize(SerializationWriter $writer): void {
        $writer->writeAdditionalData($this->additionalData);
    }

    public function getFieldDeserializers(): array {
       return [];
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @param LargeFileUploadConflictBehavior|null $conflictBehavior
     */
    public function setConflictBehavior(?LargeFileUploadConflictBehavior $conflictBehavior): void {
        $this->conflictBehavior = $conflictBehavior;
    }

    /**
     * @return LargeFileUploadConflictBehavior|null
     */
    public function getConflictBehavior(): ?LargeFileUploadConflictBehavior {
        return $this->conflictBehavior;
    }

    /**
     * @param int|null $fileSize
     */
    public function setFileSize(?int $fileSize): void {
        $this->fileSize = $fileSize;
    }

    /**
     * @return int|null
     */
    public function getFileSize(): ?int {
        return $this->fileSize;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string {
        return $this->description;
    }
}
