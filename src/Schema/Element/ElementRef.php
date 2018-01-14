<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

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

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    /**
     * @return ElementDef
     */
    public function getReferencedElement(): ElementDef
    {
        return $this->wrapped;
    }

    public function getType(): ?Type
    {
        return $this->wrapped->getType();
    }
}
