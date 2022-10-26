<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadDriveItemUploadableProperties implements Parsable, AdditionalDataHolder
{
    private ?string $name = null;
    private array $additionalData = [];
    private ?string $description = null;

    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }
    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    public function serialize(SerializationWriter $writer): void {
        $writer->writeStringValue('name', $this->name);
        $writer->writeStringValue('description', $this->description);
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
