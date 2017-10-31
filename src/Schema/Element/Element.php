<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Element extends AbstractElementSingle implements ElementItem
{
    /**
     * @return Element
     */
    public static function loadElement(
        SchemaReader $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $element = new self($schema, $node->getAttribute('name'));
        $element->setDoc(SchemaReader::getDocumentation($node));

        $reader->fillItem($element, $node);

        SchemaReader::maybeSetMax($element, $node);
        SchemaReader::maybeSetMin($element, $node);

        $xp = new \DOMXPath($node->ownerDocument);
        $xp->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        if ($xp->query('ancestor::xs:choice', $node)->length) {
            $element->setMin(0);
        }

        if ($node->hasAttribute('nillable')) {
            $element->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $element->setQualified($node->getAttribute('form') == 'qualified');
        }

        return $element;
    }
}
