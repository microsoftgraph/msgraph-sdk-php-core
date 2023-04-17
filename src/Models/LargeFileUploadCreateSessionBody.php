<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Store\BackedModel;
use Microsoft\Kiota\Abstractions\Store\BackingStore;
use Microsoft\Kiota\Abstractions\Store\BackingStoreFactorySingleton;

class LargeFileUploadCreateSessionBody implements Parsable, AdditionalDataHolder, BackedModel
{
    private BackingStore $backingStore;


    public function __construct() {
        $this->backingStore = BackingStoreFactorySingleton::getInstance()->createBackingStore();
        $this->setAdditionalData([]);
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalData(): ?array {
        /** @phpstan-ignore-next-line */
        return $this->backingStore->get('additionalData');
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData(array $value): void {
        $this->backingStore->set('additionalData', $value);
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
    public function serialize(SerializationWriter $writer): void  {
        $writer->writeAdditionalData($this->getAdditionalData());
    }

    /**
     * @inheritDoc
     */
    public function getBackingStore(): ?BackingStore {
        return $this->backingStore;
    }
}
