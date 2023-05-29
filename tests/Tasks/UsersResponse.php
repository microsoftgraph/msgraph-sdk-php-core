<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class UsersResponse implements Parsable
{
    public ?string $odataNextLink = null;
    /** @phpstan-ignore-next-line */
    public ?array $value = null;
    public function getFieldDeserializers(): array
    {
        return [
            '@odata.nextLink' => fn (ParseNode $p) => $this->setOdataNextLink($p->getStringValue()),
            'value' => fn (ParseNode $p) => $this->setValue($p->getCollectionOfObjectValues([User::class, 'createFromDiscriminator']))
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('@odata.nextLink', $this->odataNextLink);
        $writer->writeCollectionOfObjectValues('value', $this->value);
    }

    /**
     * @param string|null $nextLink
     */
    public function setOdataNextLink(?string $nextLink): void {
        $this->odataNextLink = $nextLink;
    }

    /**
     * @param array<mixed>|null $value
     */
    public function setValue(?array $value): void {
        $this->value = $value;
    }

    public static function createFromDiscriminator(ParseNode $parseNode): UsersResponse {
        return new UsersResponse();
    }

    /**
     * @return string|null
     */
    public function getOdataNextLink(): ?string
    {
        return $this->odataNextLink;
    }

    /**
     * @return array<mixed>|null
     */
    public function getValue(): ?array
    {
        return $this->value;
    }
}
