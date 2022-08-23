<?php

namespace Microsoft\Graph\Core\Tasks;

class PageResult
{
    /** @var string|null $nextLink */
    private ?string $nextLink;
    /** @var array<mixed>|null $value  */
    private ?array $value;

    /**
     * @return string|null
     */
    public function getNextLink(): ?string {
        return $this->nextLink;
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
    public function setNextLink(?string $nextLink): void{
        $this->nextLink = $nextLink;
    }

    /**
     * @param array|null $value
     */
    public function setValue(?array $value): void {
        $this->value = $value;
    }
}
