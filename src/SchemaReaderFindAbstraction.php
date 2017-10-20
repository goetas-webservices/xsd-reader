<?php
namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Base;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;
use RuntimeException;

abstract class SchemaReaderFindAbstraction extends SchemaReaderCallbackAbstraction
{
    /**
    * @param string $attributeName
    *
    * @return SchemaItem
    */
    protected function findSomeType(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        return $this->findSomeTypeFromAttribute(
            $fromThis,
            $node,
            $node->getAttribute($attributeName)
        );
    }

    /**
    * @param string $attributeName
    *
    * @return SchemaItem
    */
    protected function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        /**
        * @var SchemaItem $out
        */
        $out = $this->findSomething(
            'findType',
            $fromThis->getSchema(),
            $node,
            $attributeName
        );

        return $out;
    }
}
