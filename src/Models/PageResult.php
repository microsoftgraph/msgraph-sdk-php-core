<?php

namespace Microsoft\Graph\Core\Models;

use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class PageResult implements Parsable
{
    /** @var string|null $odataNextLink */
    private ?string $odataNextLink = null;
    /** @var array<mixed>|null $value  */
    private ?array $value = null;

    /**
     * @return string|null
     */
    public function getOdataNextLink(): ?string {
        return $this->odataNextLink;
    }

    /**
     * @return array<mixed>|null
     */
    public function getValue(): ?array {
        return $this->value;
    }

    /**
     * @param string|null $nextLink
     */
    public function setOdataNextLink(?string $nextLink): void{
        $this->odataNextLink = $nextLink;
    }

    /**
     * @param array<mixed>|null $value
     */
    public function setValue(?array $value): void {
        $this->value = $value;
    }

    public function createFromDiscriminatorValue(ParseNode $parseNode): PageResult
    {
        return new PageResult();
    }

    public function getFieldDeserializers(): array
    {
        return [
            '@odata.nextLink' => fn (ParseNode $parseNode) => $this->setOdataNextLink($parseNode->getStringValue()),
            'value' => fn (ParseNode $parseNode) => $this->setValue($parseNode->getCollectionOfPrimitiveValues())
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('@odata.nextLink', $this->getOdataNextLink());
        $writer->writeAnyValue('value', $this->getValue());
    }
}
