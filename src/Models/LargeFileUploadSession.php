<?php
namespace Microsoft\Graph\Core\Models;

use DateTime;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class LargeFileUploadSession implements Parsable, AdditionalDataHolder
{
    private ?string $uploadUrl = null;
    private ?DateTime $expirationDateTime = null;
    /** @var array<string, mixed> */
    private array $additionalData = [];
    private ?bool $isCancelled = false;
    /** @var string[]|null  */
    private ?array $nextExpectedRanges = [];

    /**
     * @param DateTime|null $expirationDateTime
     */
    public function setExpirationDateTime(?DateTime $expirationDateTime): void
    {
        $this->expirationDateTime = $expirationDateTime;
    }

    /**
     * @param string|null $uploadUrl
     */
    public function setUploadUrl(?string $uploadUrl): void
    {
        $this->uploadUrl = $uploadUrl;
    }

    /**
     * @param array<string>|null $nextExpectedRanges
     */
    public function setNextExpectedRanges(?array $nextExpectedRanges): void
    {
        $this->nextExpectedRanges = $nextExpectedRanges;
    }

    /**
     * @return array<string>|null
     */
    public function getNextExpectedRanges(): ?array
    {
        return $this->nextExpectedRanges;
    }

    /**
     * @return DateTime|null
     */
    public function getExpirationDateTime(): ?DateTime
    {
        return $this->expirationDateTime;
    }

    /**
     * @return string|null
     */
    public function getUploadUrl(): ?string
    {
        return $this->uploadUrl;
    }

    /**
    * @param bool|null $isCancelled
    */
    public function setIsCancelled(?bool $isCancelled): void
    {
            $this->isCancelled = $isCancelled;
    }

    /** @inheritDoc */
    public function getAdditionalData(): ?array
    {
        return $this->additionalData;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function setAdditionalData(array $value): void
    {
        $this->additionalData = $value;
    }

    /**
     * @param ParseNode $parseNode
     * @return LargeFileUploadSession
     */
    public static function createFromDiscriminatorValue(ParseNode $parseNode): LargeFileUploadSession
    {
        return new LargeFileUploadSession();
    }

    /**
     * @return bool|null
     */
    public function getIsCancelled(): ?bool
    {
        return $this->isCancelled;
    }


    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('uploadUrl', $this->uploadUrl);
        $writer->writeDateTimeValue('expirationDateTime', $this->expirationDateTime);
        $writer->writeBooleanValue('isCancelled', $this->isCancelled);
        $writer->writeCollectionOfPrimitiveValues('nextExpectedRanges', $this->nextExpectedRanges);
        $writer->writeAdditionalData($this->additionalData);
    }

    public function getFieldDeserializers(): array
    {
        return [
            'uploadUrl' => fn (ParseNode $parseNode) => $this->setUploadUrl($parseNode->getStringValue()),
            'expirationDateTime' => fn (ParseNode $parseNode) => $this->setExpirationDateTime($parseNode->getDateTimeValue()),
            'isCancelled' => fn (ParseNode $parseNode) => $this->setIsCancelled($parseNode->getBooleanValue()),
            /** @phpstan-ignore-next-line */
            'nextExpectedRanges' => fn (ParseNode $parseNode) => $this->setNextExpectedRanges($parseNode->getCollectionOfPrimitiveValues('string'))];
    }
}
