<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

trait ElementContainerTrait
{
    /**
     * @var ElementItem[]
     */
    protected $elements = array();

    /**
     * @return ElementItem[]
     */
    public function getElements() : array
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element)
    {
        $this->elements[] = $element;
    }
}
