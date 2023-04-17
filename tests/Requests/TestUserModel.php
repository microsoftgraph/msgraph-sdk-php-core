<?php
/**
 * Copyright (c) Microsoft Corporation.  All Rights Reserved.
 * Licensed under the MIT License.  See License in the project root
 * for license information.
 */


namespace Microsoft\Graph\Core\Test\Requests;


use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class TestUserModel implements Parsable
{

    private string $id;
    private string $name;

    public function __construct(string $id = '', string $name = '')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public static function createFromDiscriminatorValue(ParseNode $parseNode): self
    {
        return new TestUserModel();
    }

    public function getFieldDeserializers(): array
    {
        return [
            'id' => fn (ParseNode $n) => $this->setId($n->getStringValue()),
            'name' => fn (ParseNode $n) => $this->setName($n->getStringValue()),
        ];
    }

    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('id', $this->getId());
        $writer->writeStringValue('name', $this->getName());
    }
}
