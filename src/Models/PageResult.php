<?php

namespace Microsoft\Graph\Core\Models;

class PageResult
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
}
