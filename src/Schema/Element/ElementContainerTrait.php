<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

trait ElementContainerTrait
{
    /**
     * @var ElementItem[]
     */
    protected array $elements = [];

    /**
     * @return ElementItem[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element): void
    {
        $this->elements[] = $element;
    }
}
