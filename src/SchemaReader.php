<?php

namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use GoetasWebservices\XML\XSDReader\Documentation\DocumentationReader;
use GoetasWebservices\XML\XSDReader\Documentation\StandardDocumentationReader;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
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

class SchemaReader
{
    const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    const XML_NS = 'http://www.w3.org/XML/1998/namespace';

    /**
     * @var DocumentationReader
     */
    private $documentationReader;

    /**
     * @var Schema[]
     */
    private $loadedFiles = array();

    /**
     * @var string[]
     */
    protected $knownLocationSchemas = [
        'http://www.w3.org/2001/xml.xsd' => (
            __DIR__.'/Resources/xml.xsd'
        ),
        'http://www.w3.org/2001/XMLSchema.xsd' => (
            __DIR__.'/Resources/XMLSchema.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd' => (
            __DIR__.'/Resources/oasis-200401-wss-wssecurity-secext-1.0.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd' => (
            __DIR__.'/Resources/oasis-200401-wss-wssecurity-utility-1.0.xsd'
        ),
        'https://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__.'/Resources/xmldsig-core-schema.xsd'
        ),
        'http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__.'/Resources/xmldsig-core-schema.xsd'
        ),
    ];

    /**
     * @var string[]
     */
    protected static $globalSchemaInfo = array(
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd',
    );

    public function __construct(DocumentationReader $documentationReader = null)
    {
        if (null === $documentationReader) {
            $documentationReader = new StandardDocumentationReader();
        }
        $this->documentationReader = $documentationReader;
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
     * @return \Closure
     */
    private function loadAttributeGroup(
        Schema $schema,
        DOMElement $node
    ) {
        $attGroup = new AttributeGroup($schema, $node->getAttribute('name'));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schema, $node, $attGroup) {
            SchemaReader::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $schema,
                    $attGroup
                ) {
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
                            $this->findSomethingLikeAttributeGroup(
                                $schema,
                                $node,
                                $childNode,
                                $attGroup
                            );
                            break;
                    }
                }
            );
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
        if ($childNode->hasAttribute('ref')) {
            /**
             * @var AttributeItem
             */
            $attribute = $this->findSomething('findAttribute', $schema, $node, $childNode->getAttribute('ref'));
        } else {
            /**
             * @var Attribute
             */
            $attribute = $this->loadAttribute($schema, $childNode);
        }

        return $attribute;
    }

    /**
     * @return Attribute
     */
    private function loadAttribute(
        Schema $schema,
        DOMElement $node
    ) {
        $attribute = new Attribute($schema, $node->getAttribute('name'));
        $attribute->setDoc($this->getDocumentation($node));
        $this->fillItem($attribute, $node);

        if ($node->hasAttribute('nillable')) {
            $attribute->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $attribute->setQualified($node->getAttribute('form') == 'qualified');
        }
        if ($node->hasAttribute('use')) {
            $attribute->setUse($node->getAttribute('use'));
        }

        return $attribute;
    }

    /**
     * @param bool $attributeDef
     *
     * @return Closure
     */
    private function loadAttributeOrElementDef(
        Schema $schema,
        DOMElement $node,
        $attributeDef
    ) {
        $name = $node->getAttribute('name');
        if ($attributeDef) {
            $attribute = new AttributeDef($schema, $name);
            $schema->addAttribute($attribute);
        } else {
            $attribute = new ElementDef($schema, $name);
            $schema->addElement($attribute);
        }

        return function () use ($attribute, $node) {
            $this->fillItem($attribute, $node);
        };
    }

    /**
     * @return Closure
     */
    private function loadAttributeDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, true);
    }

    /**
     * @param DOMElement $node
     *
     * @return string
     */
    private function getDocumentation(DOMElement $node)
    {
        return $this->documentationReader->get($node);
    }

    /**
     * @param Schema     $schema
     * @param DOMElement $node
     * @param Schema     $parent
     *
     * @return Closure[]
     */
    private function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $this->setSchemaThingsFromNode($schema, $node, $parent);
        $functions = array();

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schema,
                &$functions
            ) {
                $callback = null;

                switch ($childNode->localName) {
                    case 'attributeGroup':
                        $callback = $this->loadAttributeGroup($schema, $childNode);
                        break;
                    case 'include':
                    case 'import':
                        $callback = $this->loadImport($schema, $childNode);
                        break;
                    case 'element':
                        $callback = $this->loadElementDef($schema, $childNode);
                        break;
                    case 'attribute':
                        $callback = $this->loadAttributeDef($schema, $childNode);
                        break;
                    case 'group':
                        $callback = $this->loadGroup($schema, $childNode);
                        break;
                    case 'complexType':
                        $callback = $this->loadComplexType($schema, $childNode);
                        break;
                    case 'simpleType':
                        $callback = $this->loadSimpleType($schema, $childNode);
                        break;
                }

                if ($callback instanceof Closure) {
                    $functions[] = $callback;
                }
            }
        );

        return $functions;
    }

    /**
     * @return GroupRef
     */
    private function loadGroupRef(Group $referenced, DOMElement $node)
    {
        $ref = new GroupRef($referenced);
        $ref->setDoc($this->getDocumentation($node));

        self::maybeSetMax($ref, $node);
        self::maybeSetMin($ref, $node);

        return $ref;
    }

    /**
     * @return InterfaceSetMinMax
     */
    private static function maybeSetMax(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if (
            $node->hasAttribute('maxOccurs')
        ) {
            $ref->setMax($node->getAttribute('maxOccurs') == 'unbounded' ? -1 : (int) $node->getAttribute('maxOccurs'));
        }

        return $ref;
    }

    /**
     * @return InterfaceSetMinMax
     */
    private static function maybeSetMin(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if ($node->hasAttribute('minOccurs')) {
            $ref->setMin((int) $node->getAttribute('minOccurs'));
        }

        return $ref;
    }

    /**
     * @param int|null $max
     */
    private function loadSequence(ElementContainer $elementContainer, DOMElement $node, $max = null)
    {
        $max =
        (
            (is_int($max) && (bool) $max) ||
            $node->getAttribute('maxOccurs') == 'unbounded' ||
            $node->getAttribute('maxOccurs') > 1
        )
            ? 2
            : null;

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $elementContainer,
                $max
            ) {
                $this->loadSequenceChildNode(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max
                );
            }
        );
    }

    /**
     * @param int|null $max
     */
    private function loadSequenceChildNode(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        switch ($childNode->localName) {
            case 'sequence':
            case 'choice':
            case 'all':
                $this->loadSequence(
                    $elementContainer,
                    $childNode,
                    $max
                );
                break;
            case 'element':
                $this->loadSequenceChildNodeLoadElement(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max
                );
                break;
            case 'group':
                $this->addGroupAsElement(
                    $elementContainer->getSchema(),
                    $node,
                    $childNode,
                    $elementContainer
                );
                break;
        }
    }

    /**
     * @param int|null $max
     */
    private function loadSequenceChildNodeLoadElement(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        if ($childNode->hasAttribute('ref')) {
            /**
             * @var ElementDef $referencedElement
             */
            $referencedElement = $this->findSomething('findElement', $elementContainer->getSchema(), $node, $childNode->getAttribute('ref'));
            $element = new ElementRef($referencedElement);
            $element->setDoc($this->getDocumentation($childNode));

            self::maybeSetMax($element, $childNode);
            self::maybeSetMin($element, $childNode);
            if ($childNode->hasAttribute('nillable')) {
                $element->setNil($childNode->getAttribute('nillable') == 'true');
            }
            if ($childNode->hasAttribute('form')) {
                $element->setQualified($childNode->getAttribute('form') == 'qualified');
            }
        } else {
            $element = $this->loadElement(
                $elementContainer->getSchema(),
                $childNode
            );
        }
        if ($max > 1) {
            /*
            * although one might think the typecast is not needed with $max being `? int $max` after passing > 1,
            * phpstan@a4f89fa still thinks it's possibly null.
            * see https://github.com/phpstan/phpstan/issues/577 for related issue
            */
            $element->setMax((int) $max);
        }
        $elementContainer->addElement($element);
    }

    private function addGroupAsElement(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        ElementContainer $elementContainer
    ) {
        /**
         * @var Group
         */
        $referencedGroup = $this->findSomething(
            'findGroup',
            $schema,
            $node,
            $childNode->getAttribute('ref')
        );

        $group = $this->loadGroupRef($referencedGroup, $childNode);
        $elementContainer->addElement($group);
    }

    /**
     * @return Closure
     */
    private function loadGroup(Schema $schema, DOMElement $node)
    {
        $group = new Group($schema, $node->getAttribute('name'));
        $group->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute('maxOccurs')) {
            /**
             * @var GroupRef
             */
            $group = self::maybeSetMax(new GroupRef($group), $node);
        }
        if ($node->hasAttribute('minOccurs')) {
            /**
             * @var GroupRef
             */
            $group = self::maybeSetMin(
                $group instanceof GroupRef ? $group : new GroupRef($group),
                $node
            );
        }

        $schema->addGroup($group);

        return function () use ($group, $node) {
            static::againstDOMNodeList(
                $node,
                function (DOMelement $node, DOMElement $childNode) use ($group) {
                    switch ($childNode->localName) {
                        case 'sequence':
                        case 'choice':
                        case 'all':
                            $this->loadSequence($group, $childNode);
                            break;
                    }
                }
            );
        };
    }

    /**
     * @param Closure|null $callback
     *
     * @return Closure
     */
    private function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        /**
         * @var bool
         */
        $isSimple = false;

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                &$isSimple
            ) {
                if ($isSimple) {
                    return;
                }
                if ($childNode->localName === 'simpleContent') {
                    $isSimple = true;
                }
            }
        );

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute('name')) : new ComplexType($schema, $node->getAttribute('name'));

        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $schema, $callback) {
            $this->fillTypeNode($type, $node, true);

            static::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $schema,
                    $type
                ) {
                    $this->loadComplexTypeFromChildNode(
                        $type,
                        $node,
                        $childNode,
                        $schema
                    );
                }
            );

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    private function loadComplexTypeFromChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode,
        Schema $schema
    ) {
        switch ($childNode->localName) {
            case 'sequence':
            case 'choice':
            case 'all':
                if ($type instanceof ElementContainer) {
                    $this->loadSequence(
                        $type,
                        $childNode
                    );
                }
                break;
            case 'attribute':
                $this->addAttributeFromAttributeOrRef(
                    $type,
                    $childNode,
                    $schema,
                    $node
                );
                break;
            case 'attributeGroup':
                $this->findSomethingLikeAttributeGroup(
                    $schema,
                    $node,
                    $childNode,
                    $type
                );
                break;
            case 'group':
                if (
                    $type instanceof ComplexType
                ) {
                    $this->addGroupAsElement(
                        $schema,
                        $node,
                        $childNode,
                        $type
                    );
                }
                break;
        }
    }

    /**
     * @param Closure|null $callback
     *
     * @return Closure
     */
    private function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType($schema, $node->getAttribute('name'));
        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $callback) {
            $this->fillTypeNode($type, $node, true);

            static::againstDOMNodeList(
                $node,
                function (DOMElement $node, DOMElement $childNode) use ($type) {
                    switch ($childNode->localName) {
                        case 'union':
                            $this->loadUnion($type, $childNode);
                            break;
                        case 'list':
                            $this->loadList($type, $childNode);
                            break;
                    }
                }
            );

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    private function loadList(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute('itemType')) {
            /**
             * @var SimpleType
             */
            $listType = $this->findSomeType($type, $node, 'itemType');
            $type->setList($listType);
        } else {
            self::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $type
                ) {
                    $this->loadTypeWithCallback(
                        $type->getSchema(),
                        $childNode,
                        function (SimpleType $list) use ($type) {
                            $type->setList($list);
                        }
                    );
                }
            );
        }
    }

    /**
     * @param string $attributeName
     *
     * @return SchemaItem
     */
    private function findSomeType(
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
    private function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        /**
         * @var SchemaItem
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
        if ($node->hasAttribute('memberTypes')) {
            $types = preg_split('/\s+/', $node->getAttribute('memberTypes'));
            foreach ($types as $typeName) {
                /**
                 * @var SimpleType
                 */
                $unionType = $this->findSomeTypeFromAttribute(
                    $type,
                    $node,
                    $typeName
                );
                $type->addUnion($unionType);
            }
        }
        self::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $type
            ) {
                $this->loadTypeWithCallback(
                    $type->getSchema(),
                    $childNode,
                    function (SimpleType $unType) use ($type) {
                        $type->addUnion($unType);
                    }
                );
            }
        );
    }

    /**
     * @param bool $checkAbstract
     */
    private function fillTypeNode(Type $type, DOMElement $node, $checkAbstract = false)
    {
        if ($checkAbstract) {
            $type->setAbstract($node->getAttribute('abstract') === 'true' || $node->getAttribute('abstract') === '1');
        }

        static::againstDOMNodeList(
            $node,
            function (DOMElement $node, DOMElement $childNode) use ($type) {
                switch ($childNode->localName) {
                    case 'restriction':
                        $this->loadRestriction($type, $childNode);
                        break;
                    case 'extension':
                        if ($type instanceof BaseComplexType) {
                            $this->loadExtension($type, $childNode);
                        }
                        break;
                    case 'simpleContent':
                    case 'complexContent':
                        $this->fillTypeNode($type, $childNode);
                        break;
                }
            }
        );
    }

    private function loadExtension(BaseComplexType $type, DOMElement $node)
    {
        $extension = new Extension();
        $type->setExtension($extension);

        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase(
                $type,
                $extension,
                $node
            );
        }
        $this->loadExtensionChildNodes($type, $node);
    }

    private function findAndSetSomeBase(
        Type $type,
        Base $setBaseOnThis,
        DOMElement $node
    ) {
        /**
         * @var Type
         */
        $parent = $this->findSomeType($type, $node, 'base');
        $setBaseOnThis->setBase($parent);
    }

    private function loadExtensionChildNodes(
        BaseComplexType $type,
        DOMElement $node
    ) {
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $type
            ) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choice':
                    case 'all':
                        if ($type instanceof ElementContainer) {
                            $this->loadSequence(
                                $type,
                                $childNode
                            );
                        }
                        break;
                    case 'attribute':
                        $this->addAttributeFromAttributeOrRef(
                            $type,
                            $childNode,
                            $type->getSchema(),
                            $node
                        );
                        break;
                    case 'attributeGroup':
                        $this->findSomethingLikeAttributeGroup(
                            $type->getSchema(),
                            $node,
                            $childNode,
                            $type
                        );
                        break;
                }
            }
        );
    }

    private function loadRestriction(Type $type, DOMElement $node)
    {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase($type, $restriction, $node);
        } else {
            self::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $type,
                    $restriction
                ) {
                    $this->loadTypeWithCallback(
                        $type->getSchema(),
                        $childNode,
                        function (Type $restType) use ($restriction) {
                            $restriction->setBase($restType);
                        }
                    );
                }
            );
        }
        self::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $restriction
            ) {
                if (
                    in_array(
                        $childNode->localName,
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
                            'whiteSpace',
                        ],
                        true
                    )
                ) {
                    $restriction->addCheck(
                        $childNode->localName,
                        [
                            'value' => $childNode->getAttribute('value'),
                            'doc' => $this->getDocumentation($childNode),
                        ]
                    );
                }
            }
        );
    }

    /**
     * @param string $typeName
     *
     * @return mixed[]
     */
    private static function splitParts(DOMElement $node, $typeName)
    {
        $prefix = null;
        $name = $typeName;
        if (strpos($typeName, ':') !== false) {
            list($prefix, $name) = explode(':', $typeName);
        }

        $namespace = $node->lookupNamespaceUri($prefix ?: '');

        return array(
            $name,
            $namespace,
            $prefix,
        );
    }

    /**
     * @param string     $finder
     * @param Schema     $schema
     * @param DOMElement $node
     * @param string     $typeName
     *
     * @throws TypeException
     *
     * @return ElementItem|Group|AttributeItem|AttributeGroup|Type
     */
    private function findSomething($finder, Schema $schema, DOMElement $node, $typeName)
    {
        list($name, $namespace) = static::splitParts($node, $typeName);

        /**
         * @var string|null
         */
        $namespace = $namespace ?: $schema->getTargetNamespace();

        try {
            /**
             * @var ElementItem|Group|AttributeItem|AttributeGroup|Type
             */
            $out = $schema->$finder($name, $namespace);

            return $out;
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", strtolower(substr($finder, 4)), $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    /**
     * @return Closure
     */
    private function loadElementDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, false);
    }

    private function fillItem(Item $element, DOMElement $node)
    {
        /**
         * @var bool
         */
        $skip = false;
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $element,
                &$skip
            ) {
                if (
                    !$skip &&
                    in_array(
                        $childNode->localName,
                        [
                            'complexType',
                            'simpleType',
                        ]
                    )
                ) {
                    $this->loadTypeWithCallback(
                        $element->getSchema(),
                        $childNode,
                        function (Type $type) use ($element) {
                            $element->setType($type);
                        }
                    );
                    $skip = true;
                }
            }
        );
        if ($skip) {
            return;
        }
        $this->fillItemNonLocalType($element, $node);
    }

    private function fillItemNonLocalType(Item $element, DOMElement $node)
    {
        if ($node->getAttribute('type')) {
            /**
             * @var Type
             */
            $type = $this->findSomeType($element, $node, 'type');
        } else {
            /**
             * @var Type
             */
            $type = $this->findSomeTypeFromAttribute(
                $element,
                $node,
                ($node->lookupPrefix(self::XSD_NS).':anyType')
            );
        }

        $element->setType($type);
    }

    /**
     * @param string $file
     * @param string $namespace
     *
     * @return Closure
     */
    private function loadImport(
        Schema $schema,
        DOMElement $node
    ) {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute('schemaLocation'));

        $namespace = $node->getAttribute('namespace');

        $keys = $this->loadImportFreshKeys($namespace, $file);

        foreach ($keys as $key) {
            if (isset($this->loadedFiles[$key])) {
                $schema->addSchema($this->loadedFiles[$key]);

                return function () {
                };
            }
        }

        return $this->loadImportFresh($namespace, $schema, $file);
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return string[]
     */
    private function loadImportFreshKeys(
        $namespace,
        $file
    ) {
        $globalSchemaInfo = $this->getGlobalSchemaInfo();

        $keys = [];

        if (isset($globalSchemaInfo[$namespace])) {
            $keys[] = $globalSchemaInfo[$namespace];
        }

        $keys[] = $this->getNamespaceSpecificFileIndex(
            $file,
            $namespace
        );

        $keys[] = $file;

        return $keys;
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Schema
     */
    private function loadImportFreshCallbacksNewSchema(
        $namespace,
        Schema $schema,
        $file
    ) {
        /**
         * @var Schema $newSchema
         */
        $newSchema = $this->setLoadedFile(
            $file,
            ($namespace ? new Schema() : $schema)
        );

        if ($namespace) {
            $newSchema->addSchema($this->getGlobalSchema());
            $schema->addSchema($newSchema);
        }

        return $newSchema;
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure[]
     */
    private function loadImportFreshCallbacks(
        $namespace,
        Schema $schema,
        $file
    ) {
        /**
         * @var string
         */
        $file = $file;

        return $this->schemaNode(
            $this->loadImportFreshCallbacksNewSchema(
                $namespace,
                $schema,
                $file
            ),
            $this->getDOM(
                isset($this->knownLocationSchemas[$file])
                    ? $this->knownLocationSchemas[$file]
                    : $file
            )->documentElement,
            $schema
        );
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure
     */
    private function loadImportFresh(
        $namespace,
        Schema $schema,
        $file
    ) {
        return function () use ($namespace, $schema, $file) {
            foreach (
                $this->loadImportFreshCallbacks(
                    $namespace,
                    $schema,
                    $file
                ) as $callback
            ) {
                $callback();
            }
        };
    }

    /**
     * @var Schema|null
     */
    protected $globalSchema;

    /**
     * @return Schema[]
     */
    private function setupGlobalSchemas(array &$callbacks)
    {
        $globalSchemas = array();
        foreach (self::$globalSchemaInfo as $namespace => $uri) {
            $this->setLoadedFile(
                $uri,
                $globalSchemas[$namespace] = $schema = new Schema()
            );
            if ($namespace === self::XSD_NS) {
                $this->globalSchema = $schema;
            }
            $xml = $this->getDOM($this->knownLocationSchemas[$uri]);
            $callbacks = array_merge($callbacks, $this->schemaNode($schema, $xml->documentElement));
        }

        return $globalSchemas;
    }

    /**
     * @return string[]
     */
    public function getGlobalSchemaInfo()
    {
        return self::$globalSchemaInfo;
    }

    /**
     * @return Schema
     */
    public function getGlobalSchema()
    {
        if (!$this->globalSchema) {
            $callbacks = array();
            $globalSchemas = $this->setupGlobalSchemas($callbacks);

            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], 'anySimpleType'));
            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], 'anyType'));

            $globalSchemas[static::XML_NS]->addSchema(
                $globalSchemas[static::XSD_NS],
                (string) static::XSD_NS
            );
            $globalSchemas[static::XSD_NS]->addSchema(
                $globalSchemas[static::XML_NS],
                (string) static::XML_NS
            );

            /**
             * @var Closure
             */
            foreach ($callbacks as $callback) {
                $callback();
            }
        }

        /**
         * @var Schema
         */
        $out = $this->globalSchema;

        return $out;
    }

    /**
     * @param DOMElement $node
     * @param string     $file
     *
     * @return Schema
     */
    private function readNode(DOMElement $node, $file = 'schema.xsd')
    {
        $fileKey = $node->hasAttribute('targetNamespace') ? $this->getNamespaceSpecificFileIndex($file, $node->getAttribute('targetNamespace')) : $file;
        $this->setLoadedFile($fileKey, $rootSchema = new Schema());

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
        return $file.'#'.$targetNamespace;
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

    private static function againstDOMNodeList(
        DOMElement $node,
        Closure $againstNodeList
    ) {
        $limit = $node->childNodes->length;
        for ($i = 0; $i < $limit; $i += 1) {
            /**
             * @var DOMNode
             */
            $childNode = $node->childNodes->item($i);

            if ($childNode instanceof DOMElement) {
                $againstNodeList(
                    $node,
                    $childNode
                );
            }
        }
    }

    private function loadTypeWithCallback(
        Schema $schema,
        DOMElement $childNode,
        Closure $callback
    ) {
        /**
         * @var Closure|null $func
         */
        $func = null;

        switch ($childNode->localName) {
            case 'complexType':
                $func = $this->loadComplexType($schema, $childNode, $callback);
                break;
            case 'simpleType':
                $func = $this->loadSimpleType($schema, $childNode, $callback);
                break;
        }

        if ($func instanceof Closure) {
            call_user_func($func);
        }
    }

    /**
     * @return Element
     */
    private function loadElement(
        Schema $schema,
        DOMElement $node
    ) {
        $element = new Element($schema, $node->getAttribute('name'));
        $element->setDoc($this->getDocumentation($node));

        $this->fillItem($element, $node);

        self::maybeSetMax($element, $node);
        self::maybeSetMin($element, $node);

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

    private function addAttributeFromAttributeOrRef(
        BaseComplexType $type,
        DOMElement $childNode,
        Schema $schema,
        DOMElement $node
    ) {
        $attribute = $this->getAttributeFromAttributeOrRef(
            $childNode,
            $schema,
            $node
        );

        $type->addAttribute($attribute);
    }

    private function findSomethingLikeAttributeGroup(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        AttributeContainer $addToThis
    ) {
        /**
         * @var AttributeItem
         */
        $attribute = $this->findSomething('findAttributeGroup', $schema, $node, $childNode->getAttribute('ref'));
        $addToThis->addAttribute($attribute);
    }

    /**
     * @param string $key
     *
     * @return Schema
     */
    private function setLoadedFile($key, Schema $schema)
    {
        $this->loadedFiles[$key] = $schema;

        return $schema;
    }

    private function setSchemaThingsFromNode(
        Schema $schema,
        DOMElement $node,
        Schema $parent = null
    ) {
        $schema->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute('targetNamespace')) {
            $schema->setTargetNamespace($node->getAttribute('targetNamespace'));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }
        $schema->setElementsQualification($node->getAttribute('elementFormDefault') == 'qualified');
        $schema->setAttributesQualification($node->getAttribute('attributeFormDefault') == 'qualified');
        $schema->setDoc($this->getDocumentation($node));
    }
}
