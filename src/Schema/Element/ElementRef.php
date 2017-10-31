<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\SchemaReader;

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
    public function getReferencedElement()
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

    /**
     * @return ElementRef
     */
    public static function loadElementRef(
        ElementDef $referenced,
        DOMElement $node
    ) {
        $ref = new self($referenced);
        $ref->setDoc(SchemaReader::getDocumentation($node));

        SchemaReader::maybeSetMax($ref, $node);
        SchemaReader::maybeSetMin($ref, $node);
        if ($node->hasAttribute('nillable')) {
            $ref->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $ref->setQualified($node->getAttribute('form') == 'qualified');
        }

        return $ref;
    }
}
