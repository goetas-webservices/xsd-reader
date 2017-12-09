<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

class ElementRef extends AbstractElementSingle
{
    /**
     * @var ElementDef
     */
    protected $wrapped;

    public function __construct(ElementDef $element)
    {
        parent::__construct($element->getSchema(), $element->getName());
        $this->wrapped = $element;
    }

    /**
     * @return ElementDef
     */
    public function getReferencedElement() : ElementDef
    {
        return $this->wrapped;
    }

    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type|null
     */
    public function getType()
    {
        return $this->wrapped->getType();
    }
}
