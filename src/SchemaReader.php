<?php
namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeRef;
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

class SchemaReader
{

    const XSD_NS = "http://www.w3.org/2001/XMLSchema";

    const XML_NS = "http://www.w3.org/XML/1998/namespace";

    /**
    * @var Schema[]
    */
    private $loadedFiles = array();

    /**
    * @var string[]
    */
    private $knownLocationSchemas = array();

    /**
    * @var string[]
    */
    private static $globalSchemaInfo = array(
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd'
    );

    public function __construct()
    {
        $this->addKnownSchemaLocation('http://www.w3.org/2001/xml.xsd', __DIR__ . '/Resources/xml.xsd');
        $this->addKnownSchemaLocation('http://www.w3.org/2001/XMLSchema.xsd', __DIR__ . '/Resources/XMLSchema.xsd');
        $this->addKnownSchemaLocation('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', __DIR__ . '/Resources/oasis-200401-wss-wssecurity-secext-1.0.xsd');
        $this->addKnownSchemaLocation('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd', __DIR__ . '/Resources/oasis-200401-wss-wssecurity-utility-1.0.xsd');
        $this->addKnownSchemaLocation('https://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd', __DIR__ . '/Resources/xmldsig-core-schema.xsd');
        $this->addKnownSchemaLocation('http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd', __DIR__ . '/Resources/xmldsig-core-schema.xsd');
    }

    /**
    * @param string $remote
    * @param string $local
    */
    public function addKnownSchemaLocation($remote, $local)
    {
        $this->knownLocationSchemas[$remote] = $local;
    }

    /**
    * @return Closure
    */
    private function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        $attGroup = new AttributeGroup($schema, $node->getAttribute("name"));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schema, $node, $attGroup) {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'attribute':
                        $attribute = $this->getAttributeFromAttributeOrRef(
                            $childNode,
                            $schema,
                            $node
                        );
                        $attGroup->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        AttributeGroup::findSomethingLikeThis(
                            $this,
                            $schema,
                            $node,
                            $childNode,
                            $attGroup
                        );
                        break;
                }
            }
        };
    }

    /**
    * @return AttributeItem
    */
    private function getAttributeFromAttributeOrRef(
        DOMElement $childNode,
        Schema $schema,
        DOMElement $node
    ) {
        if ($childNode->hasAttribute("ref")) {
            /**
            * @var AttributeItem $attribute
            */
            $attribute = $this->findSomething('findAttribute', $schema, $node, $childNode->getAttribute("ref"));
        } else {
            /**
            * @var Attribute $attribute
            */
            $attribute = $this->loadAttribute($schema, $childNode);
        }

        return $attribute;
    }

    /**
    * @return Attribute
    */
    private function loadAttribute(Schema $schema, DOMElement $node)
    {
        $attribute = new Attribute($schema, $node->getAttribute("name"));
        $attribute->setDoc($this->getDocumentation($node));
        $this->fillItem($attribute, $node);

        if ($node->hasAttribute("nillable")) {
            $attribute->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $attribute->setQualified($node->getAttribute("form") == "qualified");
        }
        if ($node->hasAttribute("use")) {
            $attribute->setUse($node->getAttribute("use"));
        }
        return $attribute;
    }

    /**
    * @return Closure
    */
    private function loadAttributeDef(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeDef($schema, $node->getAttribute("name"));

        $schema->addAttribute($attribute);

        return function () use ($attribute, $node) {
            $this->fillItem($attribute, $node);
        };
    }

    /**
     * @param DOMElement $node
     * @return string
     */
    private function getDocumentation(DOMElement $node)
    {
        $doc = '';
        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName == "annotation") {
                foreach ($childNode->childNodes as $subChildNode) {
                    if ($subChildNode->localName == "documentation") {
                        $doc .= ($subChildNode->nodeValue);
                    }
                }
            }
        }
        $doc = preg_replace('/[\t ]+/', ' ', $doc);
        return trim($doc);
    }

    private function setSchemaThingsFromNode(
        Schema $schema,
        DOMElement $node,
        Schema $parent = null
    ) {
        $schema->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("targetNamespace")) {
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }
        $schema->setElementsQualification($node->getAttribute("elementFormDefault") == "qualified");
        $schema->setAttributesQualification($node->getAttribute("attributeFormDefault") == "qualified");
        $schema->setDoc($this->getDocumentation($node));
    }

    private function getClosuresFromChildNode(
        Schema $schema,
        DOMElement $childNode,
        array & $functions
    ) {
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

        if (isset($methods[$childNode->localName])) {
            $method = $methods[$childNode->localName];

            $functions[] = $this->$method($schema, $childNode);
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param Schema $parent
     * @return array
     */
    private function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $this->setSchemaThingsFromNode($schema, $node, $parent);
        $functions = array();

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $this->getClosuresFromChildNode(
                    $schema,
                    $childNode,
                    $functions
                );
            }
        }

        return $functions;
    }

    /**
    * @return Element
    */
    private function loadElement(Schema $schema, DOMElement $node)
    {
        $element = new Element($schema, $node->getAttribute("name"));
        $element->setDoc($this->getDocumentation($node));

        $this->fillItem($element, $node);

        static::maybeSetMax($element, $node);
        if ($node->hasAttribute("minOccurs")) {
            $element->setMin((int)$node->getAttribute("minOccurs"));
        }

        $xp = new \DOMXPath($node->ownerDocument);
        $xp->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        if ($xp->query('ancestor::xs:choice', $node)->length) {
            $element->setMin(0);
        }

        if ($node->hasAttribute("nillable")) {
            $element->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $element->setQualified($node->getAttribute("form") == "qualified");
        }
        return $element;
    }

    /**
    * @return GroupRef
    */
    private function loadGroupRef(Group $referenced, DOMElement $node)
    {
        $ref = new GroupRef($referenced);
        $ref->setDoc($this->getDocumentation($node));

        static::maybeSetMax($ref, $node);
        if ($node->hasAttribute("minOccurs")) {
            $ref->setMin((int)$node->getAttribute("minOccurs"));
        }

        return $ref;
    }

    /**
    * @return ElementRef
    */
    private function loadElementRef(ElementDef $referenced, DOMElement $node)
    {
        $ref = new ElementRef($referenced);
        $this->setDoc($ref, $node);

        static::maybeSetMax($ref, $node);
        if ($node->hasAttribute("minOccurs")) {
            $ref->setMin((int)$node->getAttribute("minOccurs"));
        }
        if ($node->hasAttribute("nillable")) {
            $ref->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $ref->setQualified($node->getAttribute("form") == "qualified");
        }

        return $ref;
    }

    private function setDoc(Item $ref, DOMElement $node)
    {
        $ref->setDoc($this->getDocumentation($node));
    }

    private static function maybeSetMax(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if (
            $node->hasAttribute("maxOccurs")
        ) {
            $ref->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }
    }

    /**
    * @return AttributeRef
    */
    private function loadAttributeRef(AttributeDef $referencedAttribiute, DOMElement $node)
    {
        $attribute = new AttributeRef($referencedAttribiute);
        $this->setDoc($attribute, $node);

        if ($node->hasAttribute("nillable")) {
            $attribute->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $attribute->setQualified($node->getAttribute("form") == "qualified");
        }
        if ($node->hasAttribute("use")) {
            $attribute->setUse($node->getAttribute("use"));
        }
        return $attribute;
    }

    /**
    * @param int|null $max
    */
    private function loadSequence(ElementContainer $elementContainer, DOMElement $node, $max = null)
    {
        $max = $max || $node->getAttribute("maxOccurs") == "unbounded" || $node->getAttribute("maxOccurs") > 1 ? 2 : null;

        foreach ($node->childNodes as $childNode) {

            switch ($childNode->localName) {
                case 'choice':
                case 'sequence':
                case 'all':
                    $this->loadSequence($elementContainer, $childNode, $max);
                    break;
                case 'element':
                    if ($childNode->hasAttribute("ref")) {
                        /**
                        * @var ElementDef $referencedElement
                        */
                        $referencedElement = $this->findSomething('findElement', $elementContainer->getSchema(), $node, $childNode->getAttribute("ref"));
                        $element = $this->loadElementRef($referencedElement, $childNode);
                    } else {
                        $element = $this->loadElement($elementContainer->getSchema(), $childNode);
                    }
                    if ($max) {
                        $element->setMax($max);
                    }
                    $elementContainer->addElement($element);
                    break;
                case 'group':
                    /**
                    * @var Group $referencedGroup
                    */
                    $referencedGroup = $this->findSomething('findGroup', $elementContainer->getSchema(), $node, $childNode->getAttribute("ref"));

                    $group = $this->loadGroupRef($referencedGroup, $childNode);
                    $elementContainer->addElement($group);
                    break;
            }
        }
    }

    private function maybeLoadSequenceFromElementContainer(
        BaseComplexType $type,
        DOMElement $childNode
    ) {
        if (! ($type instanceof ElementContainer)) {
            throw new RuntimeException(
                '$type passed to ' .
                __FUNCTION__ .
                'expected to be an instance of ' .
                ElementContainer::class .
                ' when child node localName is "group", ' .
                get_class($type) .
                ' given.'
            );
        }
        $this->loadSequence($type, $childNode);
    }

    /**
    * @return Closure
    */
    private function loadGroup(Schema $schema, DOMElement $node)
    {
        $group = new Group($schema, $node->getAttribute("name"));
        $group->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("maxOccurs")) {
            static::maybeSetMax(new GroupRef($group), $node);
        }
        if ($node->hasAttribute("minOccurs")) {
            $group = new GroupRef($group);
            $group->setMin((int)$node->getAttribute("minOccurs"));
        }

        $schema->addGroup($group);

        return function () use ($group, $node) {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choice':
                    case 'all':
                        $this->loadSequence($group, $childNode);
                        break;
                }
            }
        };
    }

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    private function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        $isSimple = false;

        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName === "simpleContent") {
                $isSimple = true;
                break;
            }
        }

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute("name")) : new ComplexType($schema, $node->getAttribute("name"));

        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $schema, $callback) {

            $this->fillTypeNode($type, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choice':
                    case 'all':
                        $this->maybeLoadSequenceFromElementContainer(
                            $type,
                            $childNode
                        );
                        break;
                    case 'attribute':
                        $attribute = $this->getAttributeFromAttributeOrRef(
                            $childNode,
                            $schema,
                            $node
                        );

                        $type->addAttribute($attribute);
                        break;
                    case 'group':
                        if (! ($type instanceof ComplexType)) {
                            throw new RuntimeException(
                                '$type passed to ' .
                                __FUNCTION__ .
                                'expected to be an instance of ' .
                                ComplexType::class .
                                ' when child node localName is "group", ' .
                                get_class($type) .
                                ' given.'
                            );
                        }

                        /**
                        * @var Group $referencedGroup
                        */
                        $referencedGroup = $this->findSomething('findGroup', $schema, $node, $childNode->getAttribute("ref"));
                        $group = $this->loadGroupRef($referencedGroup, $childNode);
                        $type->addElement($group);
                        break;
                    case 'attributeGroup':
                        AttributeGroup::findSomethingLikeThis(
                            $this,
                            $schema,
                            $node,
                            $childNode,
                            $type
                        );
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    private function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType($schema, $node->getAttribute("name"));
        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $callback) {
            $this->fillTypeNode($type, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'union':
                        $this->loadUnion($type, $childNode);
                        break;
                    case 'list':
                        $this->loadList($type, $childNode);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    private function loadList(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute("itemType")) {
            /**
            * @var SimpleType $listType
            */
            $listType = $this->findSomeType($type, $node, 'itemType');
            $type->setList($listType);
        } else {
            $addCallback = function (SimpleType $list) use ($type) {
                $type->setList($list);
            };

            $this->loadTypeWithCallbackOnChildNodes(
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
    }

    private function loadTypeWithCallbackOnChildNodes(
        Schema $schema,
        DOMNode $node,
        Closure $callback
    ) {
        foreach ($node->childNodes as $childNode) {
            $this->loadTypeWithCallback($schema, $childNode, $callback);
        }
    }

    private function loadTypeWithCallback(
        Schema $schema,
        DOMNode $childNode,
        Closure $callback
    ) {
        if (! ($childNode instanceof DOMElement)) {
            return;
        }
        switch ($childNode->localName) {
            case 'complexType':
                $childNode = $childNode;
                call_user_func(
                    $this->loadComplexType(
                        $schema,
                        $childNode,
                        $callback
                    )
                );
                break;
            case 'simpleType':
                call_user_func(
                    $this->loadSimpleType($schema, $childNode, $callback)
                );
                break;
        }
    }

    /**
    * @return SchemaItem
    */
    private function findSomeType(
        SchemaItem $fromThis,
        DOMElement $node,
        string $attributeName
    ) {
        return $this->findSomeTypeFromAttribute(
            $fromThis,
            $node,
            $node->getAttribute($attributeName)
        );
    }

    /**
    * @return SchemaItem
    */
    private function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        string $attributeName
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

    private function loadUnion(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute("memberTypes")) {
            $types = preg_split('/\s+/', $node->getAttribute("memberTypes"));
            foreach ($types as $typeName) {
                /**
                * @var SimpleType $unionType
                */
                $unionType = $this->findSomeTypeFromAttribute(
                    $type,
                    $node,
                    $typeName
                );
                $type->addUnion($unionType);
            }
        }
        $addCallback = function (SimpleType $unType) use ($type) {
            $type->addUnion($unType);
        };

        $this->loadTypeWithCallbackOnChildNodes(
            $type->getSchema(),
            $node,
            $addCallback
        );
    }

    /**
    * @param bool $checkAbstract
    */
    private function fillTypeNode(Type $type, DOMElement $node, $checkAbstract = true)
    {

        if ($checkAbstract) {
            $type->setAbstract($node->getAttribute("abstract") === "true" || $node->getAttribute("abstract") === "1");
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'restriction':
                    $this->loadRestriction($type, $childNode);
                    break;
                case 'extension':
                    $this->maybeLoadExtensionFromBaseComplexType(
                        $type,
                        $childNode
                    );
                    break;
                case 'simpleContent':
                case 'complexContent':
                    $this->fillTypeNode($type, $childNode, false);
                    break;
            }
        }
    }

    private function loadExtension(BaseComplexType $type, DOMElement $node)
    {
        $extension = new Extension();
        $type->setExtension($extension);

        if ($node->hasAttribute("base")) {
            $this->findAndSetSomeBase(
                $type,
                $extension,
                $node
            );
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'sequence':
                case 'choice':
                case 'all':
                    $this->maybeLoadSequenceFromElementContainer(
                        $type,
                        $childNode
                    );
                    break;
                case 'attribute':
                    $attribute = $this->getAttributeFromAttributeOrRef(
                        $childNode,
                        $type->getSchema(),
                        $node
                    );
                    $type->addAttribute($attribute);
                    break;
                case 'attributeGroup':
                    AttributeGroup::findSomethingLikeThis(
                        $this,
                        $type->getSchema(),
                        $node,
                        $childNode,
                        $type
                    );
                    break;
            }
        }
    }

    private function findAndSetSomeBase(
        Type $type,
        Base $setBaseOnThis,
        DOMElement $node
    ) {
        /**
        * @var Type $parent
        */
        $parent = $this->findSomeType($type, $node, 'base');
        $setBaseOnThis->setBase($parent);
    }

    private function maybeLoadExtensionFromBaseComplexType(
        Type $type,
        DOMElement $childNode
    ) {
        if (! ($type instanceof BaseComplexType)) {
            throw new RuntimeException(
                'Argument 1 passed to ' .
                __METHOD__ .
                ' needs to be an instance of ' .
                BaseComplexType::class .
                ' when passed onto ' .
                static::class .
                '::loadExtension(), ' .
                get_class($type) .
                ' given.'
            );
        }
        $this->loadExtension($type, $childNode);
    }

    private function loadRestriction(Type $type, DOMElement $node)
    {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute("base")) {
            $this->findAndSetSomeBase($type, $restriction, $node);
        } else {
            $addCallback = function (Type $restType) use ($restriction) {
                $restriction->setBase($restType);
            };

            $this->loadTypeWithCallbackOnChildNodes(
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
        foreach ($node->childNodes as $childNode) {
            if (in_array($childNode->localName,
                [
                    'enumeration',
                    'pattern',
                    'length',
                    'minLength',
                    'maxLength',
                    'minInclusive',
                    'maxInclusive',
                    'minExclusive',
                    'maxExclusive',
                    'fractionDigits',
                    'totalDigits',
                    'whiteSpace'
                ], true)) {
                $restriction->addCheck($childNode->localName,
                    [
                        'value' => $childNode->getAttribute("value"),
                        'doc' => $this->getDocumentation($childNode)
                    ]);
            }
        }
    }

    /**
    * @param string $typeName
    *
    * @return mixed[]
    */
    private static function splitParts(DOMElement $node, $typeName)
    {
        $namespace = null;
        $prefix = null;
        $name = $typeName;
        if (strpos($typeName, ':') !== false) {
            list ($prefix, $name) = explode(':', $typeName);
        }

        $namespace = $node->lookupNamespaceUri($prefix ?: '');
        return array(
            $name,
            $namespace,
            $prefix
        );
    }

    /**
     *
     * @param string $finder
     * @param Schema $schema
     * @param DOMElement $node
     * @param string $typeName
     * @throws TypeException
     * @return ElementItem|Group|AttributeItem|AttributeGroup|Type
     */
    public function findSomething($finder, Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);

        $namespace = $namespace ?: $schema->getTargetNamespace();

        try {
            return $schema->$finder($name, $namespace);
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", strtolower(substr($finder, 4)), $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    /**
    * @return Closure
    */
    private function loadElementDef(Schema $schema, DOMElement $node)
    {
        $element = new ElementDef($schema, $node->getAttribute("name"));
        $schema->addElement($element);

        return function () use ($element, $node) {
            $this->fillItem($element, $node);
        };
    }

    private function fillItem(Item $element, DOMElement $node)
    {
        $localType = null;
        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'complexType':
                case 'simpleType':
                    $localType = $childNode;
                    break 2;
            }
        }

        if ($localType) {
            $addCallback = function (Type $type) use ($element) {
                $element->setType($type);
            };
            $this->loadTypeWithCallback(
                $element->getSchema(),
                $localType,
                $addCallback
            );
        } else {

            if ($node->getAttribute("type")) {
                /**
                * @var Type $type
                */
                $type = $this->findSomeType($element, $node, 'type');
            } else {
                /**
                * @var Type $type
                */
                $type = $this->findSomeTypeFromAttribute(
                    $element,
                    $node,
                    ($node->lookupPrefix(self::XSD_NS) . ':anyType')
                );
            }

            $element->setType($type);
        }
    }

    /**
    * @return Closure
    */
    private function loadImport(Schema $schema, DOMElement $node)
    {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute("schemaLocation"));
        if ($node->hasAttribute("namespace")
            && isset(self::$globalSchemaInfo[$node->getAttribute("namespace")])
            && isset($this->loadedFiles[self::$globalSchemaInfo[$node->getAttribute("namespace")]])
        ) {

            $schema->addSchema($this->loadedFiles[self::$globalSchemaInfo[$node->getAttribute("namespace")]]);

            return function () {
            };
        } elseif ($node->hasAttribute("namespace")
            && isset($this->loadedFiles[$this->getNamespaceSpecificFileIndex($file, $node->getAttribute("namespace"))])) {
            $schema->addSchema($this->loadedFiles[$this->getNamespaceSpecificFileIndex($file, $node->getAttribute("namespace"))]);
            return function () {
            };
        } elseif (isset($this->loadedFiles[$file])) {
            $schema->addSchema($this->loadedFiles[$file]);
            return function () {
            };
        }

        if (!$node->getAttribute("namespace")) {
            $this->loadedFiles[$file] = $newSchema = $schema;
        } else {
            $this->loadedFiles[$file] = $newSchema = new Schema();
            $newSchema->addSchema($this->getGlobalSchema());
        }

        $xml = $this->getDOM(isset($this->knownLocationSchemas[$file]) ? $this->knownLocationSchemas[$file] : $file);

        $callbacks = $this->schemaNode($newSchema, $xml->documentElement, $schema);

        if ($node->getAttribute("namespace")) {
            $schema->addSchema($newSchema);
        }


        return function () use ($callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func($callback);
            }
        };
    }

    /**
    * @var Schema|null
    */
    private $globalSchema;

    /**
     *
     * @return Schema
     */
    public function getGlobalSchema()
    {
        if (!$this->globalSchema) {
            $callbacks = array();
            $globalSchemas = array();
            foreach (self::$globalSchemaInfo as $namespace => $uri) {
                $this->loadedFiles[$uri] = $globalSchemas [$namespace] = $schema = new Schema();
                if ($namespace === self::XSD_NS) {
                    $this->globalSchema = $schema;
                }
                $xml = $this->getDOM($this->knownLocationSchemas[$uri]);
                $callbacks = array_merge($callbacks, $this->schemaNode($schema, $xml->documentElement));
            }

            $globalSchemas[self::XSD_NS]->addType(new SimpleType($globalSchemas[self::XSD_NS], "anySimpleType"));
            $globalSchemas[self::XSD_NS]->addType(new SimpleType($globalSchemas[self::XSD_NS], "anyType"));

            $globalSchemas[self::XML_NS]->addSchema($globalSchemas[self::XSD_NS], self::XSD_NS);
            $globalSchemas[self::XSD_NS]->addSchema($globalSchemas[self::XML_NS], self::XML_NS);

            foreach ($callbacks as $callback) {
                $callback();
            }
        }

        /**
        * @var Schema $out
        */
        $out = $this->globalSchema;

        return $out;
    }

    /**
     * @param DOMElement $node
     * @param string  $file
     *
     * @return Schema
     */
    public function readNode(DOMElement $node, $file = 'schema.xsd')
    {
        $fileKey = $node->hasAttribute('targetNamespace') ? $this->getNamespaceSpecificFileIndex($file, $node->getAttribute('targetNamespace')) : $file;
        $this->loadedFiles[$fileKey] = $rootSchema = new Schema();

        $rootSchema->addSchema($this->getGlobalSchema());
        $callbacks = $this->schemaNode($rootSchema, $node);

        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }

    /**
     * It is possible that a single file contains multiple <xsd:schema/> nodes, for instance in a WSDL file.
     *
     * Each of these  <xsd:schema/> nodes typically target a specific namespace. Append the target namespace to the
     * file to distinguish between multiple schemas in a single file.
     *
     * @param string $file
     * @param string $targetNamespace
     *
     * @return string
     */
    private function getNamespaceSpecificFileIndex($file, $targetNamespace)
    {
        return $file . '#' . $targetNamespace;
    }

    /**
     * @param string $content
     * @param string $file
     *
     * @return Schema
     *
     * @throws IOException
     */
    public function readString($content, $file = 'schema.xsd')
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->loadXML($content)) {
            throw new IOException("Can't load the schema");
        }
        $xml->documentURI = $file;

        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     *
     * @return Schema
     */
    public function readFile($file)
    {
        $xml = $this->getDOM($file);
        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     *
     * @return DOMDocument
     *
     * @throws IOException
     */
    private function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->load($file)) {
            throw new IOException("Can't load the file $file");
        }
        return $xml;
    }
}
