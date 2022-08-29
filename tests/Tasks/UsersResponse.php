<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class UsersResponse implements Parsable
{
    public ?string $nextLink = null;
    public ?array $value = null;
    public function getFieldDeserializers(): array
    {
        return [
            '@odata.nextLink' => fn (ParseNode $p) => $this->setNextLink($p->getStringValue()),
            'value' => fn (ParseNode $p) => $this->setValue($p->getCollectionOfObjectValues([User::class, 'createFromDiscriminator']))
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('@odata.nextLink', $this->nextLink);
        $writer->writeCollectionOfObjectValues('value', $this->value);
    }

    /**
     * @param string|null $nextLink
     */
    public function setNextLink(?string $nextLink): void {
        $this->nextLink = $nextLink;
    }

    /**
     * @param array|null $value
     */
    public function setValue(?array $value): void {
        $this->value = $value;
    }

    public static function createFromDiscriminator(ParseNode $parseNode): UsersResponse {
        return new UsersResponse();
    }
}
