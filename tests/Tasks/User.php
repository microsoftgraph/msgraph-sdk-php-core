<?php

namespace Microsoft\Graph\Core\Test\Tasks;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class User implements Parsable, AdditionalDataHolder
{
    private ?string $displayName = null;
    private ?string $mail = null;
    private ?string $givenName = null;
    private ?array $businessPhones = [];
    private ?string $mobilePhone = null;
    private ?string $id = null;
    private array $additionalData = [];

    /**
     * @param string|null $id
     */
    public function setId(?string $id): void {
        $this->id = $id;
    }
    public function getFieldDeserializers(): array
    {
        return [
            'id' => fn (ParseNode $p) => $this->setId($p->getStringValue()),
            'displayName' => fn (ParseNode $p) => $this->setDisplayName($p->getStringValue()),
            'mail' => fn (ParseNode $p) => $this->setMail($p->getStringValue()),
            'givenName' => fn (ParseNode $p) => $this->setGivenName($p->getStringValue()),
            'mobilePhone' => fn (ParseNode $p) => $this->setMobilePhone($p->getStringValue()),
            'businessPhones' => fn (ParseNode $p) => $this->setBusinessPhones($p->getCollectionOfPrimitiveValues())
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('displayName', $this->displayName);
        $writer->writeStringValue('email', $this->mail);
        $writer->writeCollectionOfPrimitiveValues('businessPhones', $this->businessPhones);
        $writer->writeStringValue('givenName', $this->givenName);
        $writer->writeStringValue('mobilePhone', $this->mobilePhone);
        $writer->writeStringValue('id', $this->id);
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $value): void
    {
        $this->additionalData = $value;
    }

    /**
     * @param string $displayName
     */
    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    /**
     * @param string|null $mail
     */
    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    /**
     * @param array|null $businessPhones
     */
    public function setBusinessPhones(?array $businessPhones): void
    {
        $this->businessPhones = $businessPhones;
    }

    /**
     * @param string|null $givenName
     */
    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    public static function createFromDiscriminator(ParseNode $parseNode): User {
        return new User();
    }

    /**
     * @param string|null $mobilePhone
     */
    public function setMobilePhone(?string $mobilePhone): void
    {
        $this->mobilePhone = $mobilePhone;
    }
}
