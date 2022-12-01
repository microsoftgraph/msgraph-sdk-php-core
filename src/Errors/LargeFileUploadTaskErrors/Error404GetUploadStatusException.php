<?php

namespace Microsoft\Graph\Core\Errors\LargeFileUploadTaskErrors;

use Microsoft\Graph\Core\Errors\Common\MainError;
use Microsoft\Kiota\Abstractions\ApiException;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Store\BackedModel;
use Microsoft\Kiota\Abstractions\Store\BackingStore;
use Microsoft\Kiota\Abstractions\Store\BackingStoreFactorySingleton;

class Error404GetUploadStatusException extends ApiException implements AdditionalDataHolder, BackedModel, Parsable
{
    /**
     * @var BackingStore $backingStore Stores model information.
     */
    private BackingStore $backingStore;

    /**
     * Instantiates a new ODataError and sets the default values.
     */
    public function __construct() {
        parent::__construct('The upload session does not exist.');
        $this->backingStore = BackingStoreFactorySingleton::getInstance()->createBackingStore();
        $this->setAdditionalData([]);
    }

    /**
     * Creates a new instance of the appropriate class based on discriminator value
     * @param ParseNode $parseNode The parse node to use to read the discriminator value and create the object
     * @return Error404GetUploadStatusException
     */
    public static function createFromDiscriminatorValue(ParseNode $parseNode): Error404GetUploadStatusException  {
        return new Error404GetUploadStatusException();
    }

    /**
     * Gets the additionalData property value. Stores additional data not described in the OpenAPI description found when deserializing. Can be used for serialization as well.
     * @return array<string, mixed>
     */
    public function getAdditionalData(): ?array
    {
        return $this->getBackingStore()->get('additionalData');
    }

    /**
     * Gets the backingStore property value. Stores model information.
     * @return BackingStore
     */
    public function getBackingStore(): BackingStore
    {
        return $this->backingStore;
    }

    /**
     * Gets the error property value. The error property
     * @return MainError|null
     */
    public function getError(): ?MainError {
        return $this->getBackingStore()->get('error');
    }

    /**
     * The deserialization information for the current model
     * @return array<string, callable>
     */
    public function getFieldDeserializers(): array
    {
        $o = $this;
        return [
            'error' => fn(ParseNode $n) => $o->setError($n->getObjectValue([Error404GetUploadStatusException::class, 'createFromDiscriminatorValue'])),
        ];
    }

    /**
     * Serializes information the current object
     * @param SerializationWriter $writer Serialization writer to use to serialize this model
     */
    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeObjectValue('error', $this->getError());
        $writer->writeAdditionalData($this->getAdditionalData());
    }

    /**
     * Sets the additionalData property value. Stores additional data not described in the OpenAPI description found when deserializing. Can be used for serialization as well.
     * @param array<string,mixed> $value Value to set for the AdditionalData property.
     */
    public function setAdditionalData(?array $value): void
    {
        $this->getBackingStore()->set('additionalData', $value);
    }

    /**
     * Sets the backingStore property value. Stores model information.
     * @param BackingStore $value Value to set for the BackingStore property.
     */
    public function setBackingStore(BackingStore $value): void
    {
        $this->backingStore = $value;
    }

    /**
     * Sets the error property value. The error property
     * @param Error404GetUploadStatusException|null $value Value to set for the error property.
     */
    public function setError(?Error404GetUploadStatusException $value): void
    {
        $this->getBackingStore()->set('error', $value);
    }
}


