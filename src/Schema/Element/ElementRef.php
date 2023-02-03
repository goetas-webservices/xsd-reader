<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

class ElementRef extends AbstractElementSingle
{
    /**
     * @var ElementRef
     */
    protected $wrapped;

    public function __construct(ElementRef $element)
    {
        parent::__construct($element->getSchema(), $element->getName());
        $this->wrapped = $element;
    }

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    public function getReferencedElement(): ElementRef
    {
        return $this->wrapped;
    }

    public function getType(): ?Type
    {
        return $this->wrapped->getType();
    }
}
