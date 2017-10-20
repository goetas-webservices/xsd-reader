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

abstract class SchemaReaderSchemaAbstraction extends SchemaReaderFillAbstraction
{    protected function setSchemaThingsFromNode(
        Schema $schema,
        DOMElement $node,
        Schema $parent = null
    ) {
        $schema->setDoc(static::getDocumentation($node));

        if ($node->hasAttribute("targetNamespace")) {
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }
        $schema->setElementsQualification($node->getAttribute("elementFormDefault") == "qualified");
        $schema->setAttributesQualification($node->getAttribute("attributeFormDefault") == "qualified");
        $schema->setDoc(static::getDocumentation($node));
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param Schema $parent
     * @return Closure[]
     */
    protected function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $this->setSchemaThingsFromNode($schema, $node, $parent);
        $functions = array();

        static $methods = [
            'include' => 'loadImport',
            'import' => 'loadImport',
            'element' => 'loadElementDef',
            'attribute' => 'loadAttributeDef',
            'attributeGroup' => 'loadAttributeGroup',
            'group' => 'loadGroup',
            'complexType' => 'loadComplexType',
            'simpleType' => 'loadSimpleType',
        ];

        foreach ($node->childNodes as $childNode) {
            $callback = $this->maybeCallMethod(
                $methods,
                (string) $childNode->localName,
                $childNode,
                $schema,
                $childNode
            );

            if ($callback instanceof Closure) {
                $functions[] = $callback;
            }
        }

        return $functions;
    }
}
