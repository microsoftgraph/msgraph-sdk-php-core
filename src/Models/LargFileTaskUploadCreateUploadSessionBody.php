<?php
namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargFileTaskUploadCreateUploadSessionBody implements Parsable, AdditionalDataHolder {

    /** @var array $additionalData */
    private array $additionalData = [];
    private string $conflictBehavior = 'rename';

    private int $fileSize = 0;
    private ?string $oDataType = null;
    private ?string $name = null;
    public function serialize(SerializationWriter $writer): void {
        $writer->writeStringValue('name', $this->name);
        $writer->writeStringValue('@microsoft.graph.conflictBehavior', $this->conflictBehavior);
        $writer->writeStringValue('@odata.type', $this->oDataType);
        $writer->writeIntegerValue('fileSize', $this->fileSize);
        $writer->writeAdditionalData($this->additionalData);
    }

    /**
     * @return int
     */
    public function getFileSize(): int {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     */
    public function setFileSize(int $fileSize): void {
        $this->fileSize = $fileSize;
    }
    public function getFieldDeserializers(): array {
        return [
        'name' => fn (ParseNode $parseNode) => $this->setName($parseNode->getStringValue()),
        '@microsoft.graph.conflictBehavior' => fn (ParseNode $parseNode) => $this->setConflictBehavior($parseNode->getStringValue()),
        'fileSize' => fn (ParseNode $parseNode) => $this->setFileSize($parseNode->getIntegerValue()),
        '@odata.type' => fn (ParseNode $parseNode) => $this->setOdataType($parseNode->getStringValue())
      ];
    }

    public function setConflictBehavior(string $value): void {
        $this->conflictBehavior = $value;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @param array $value
     */
    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }

    /**
     * @returns array
     */
    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    /**
     * @return string
     */
    public function getConflictBehavior(): string {
        return $this->conflictBehavior;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $oDataType
     */
    public function setODataType(string $oDataType): void {
        $this->oDataType = $oDataType;
    }

    /**
     * @return string
     */
    public function getODataType(): string {
        return $this->oDataType;
    }
}