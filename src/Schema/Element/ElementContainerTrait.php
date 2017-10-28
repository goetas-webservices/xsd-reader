<?php

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
    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element)
    {
        $this->elements[] = $element;
    }
}
