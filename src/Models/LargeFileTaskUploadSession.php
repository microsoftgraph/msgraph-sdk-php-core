<?php
namespace Microsoft\Graph\Core\Models;

use DateTime;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileTaskUploadSession implements Parsable, AdditionalDataHolder
{
    private ?string $uploadUrl = null;
    private ?DateTime $expirationDateTime = null;
    private array $additionalData = [];
    private bool $isCancelled = false;
    private array $nextExpectedRanges = [];

    /**
     * @param DateTime $expirationDateTime
     */
    public function setExpirationDateTime(DateTime $expirationDateTime): void {
        $this->expirationDateTime = $expirationDateTime;
    }

    /**
     * @param string $uploadUrl
     */
    public function setUploadUrl(string $uploadUrl): void {
        $this->uploadUrl = $uploadUrl;
    }

    /**
     * @param array $nextExpectedRanges
     */
    public function setNextExpectedRanges(array $nextExpectedRanges): void {
        $this->nextExpectedRanges = $nextExpectedRanges;
    }

    /**
     * @return array
     */
    public function getNextExpectedRanges(): array {
        return $this->nextExpectedRanges;
    }

    /**
     * @return DateTime
     */
    public function getExpirationDateTime(): DateTime{
        return $this->expirationDateTime;
    }

    /**
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->uploadUrl;
    }

    /**
    * @param bool $isCancelled
    */
    public function setIsCancelled(bool $isCancelled): void {
            $this->isCancelled = $isCancelled;
    }

    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    /**
     * @param array $value
     */
    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }

public static function createFromDiscriminatorValue(ParseNode $parseNode): LargeFileTaskUploadSession {
        return new LargeFileTaskUploadSession();
    }

    public function serialize(SerializationWriter $writer): void {
        $writer->writeStringValue('uploadUrl', $this->uploadUrl);
        $writer->writeDateTimeValue('expirationDateTime', $this->expirationDateTime);
        $writer->writeBooleanValue('isCancelled', $this->isCancelled);
        $writer->writeCollectionOfPrimitiveValues('nextExpectedRanges', $this->nextExpectedRanges);
        $writer->writeAdditionalData($this->additionalData);
    }

    public function getFieldDeserializers(): array {
        return [
            'uploadUrl' => fn (ParseNode $parseNode) => $this->setUploadUrl($parseNode->getStringValue()),
            'expirationDateTime' => fn (ParseNode $parseNode) => $this->setExpirationDateTime($parseNode->getDateTimeValue()),
            'isCancelled' => fn (ParseNode $parseNode) => $this->setIsCancelled($parseNode->getBooleanValue()),
            'nextExpectedRanges' => fn (ParseNode $parseNode) => $this->setNextExpectedRanges($parseNode->getCollectionOfPrimitiveValues('string'))];
    }
}