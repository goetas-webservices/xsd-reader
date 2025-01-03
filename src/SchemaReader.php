<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader;

use GoetasWebservices\XML\XSDReader\Documentation\DocumentationReader;
use GoetasWebservices\XML\XSDReader\Documentation\StandardDocumentationReader;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeRef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeSingle;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\CustomAttribute;
use GoetasWebservices\XML\XSDReader\Schema\Element\AbstractElementSingle;
use GoetasWebservices\XML\XSDReader\Schema\Element\Any\Any;
use GoetasWebservices\XML\XSDReader\Schema\Element\Any\ProcessContents;
use GoetasWebservices\XML\XSDReader\Schema\Element\Choice;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetAbstract;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetDefault;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetFixed;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Element\Sequence;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Base;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\RestrictionType;
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
    public const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    public const XML_NS = 'http://www.w3.org/XML/1998/namespace';

    private DocumentationReader $documentationReader;

    /**
     * @var Schema[]
     */
    private array $loadedFiles = [];

    /**
     * @var Schema[][]
     */
    private array $loadedSchemas = [];

    /**
     * @var string[]
     */
    protected array $knownLocationSchemas = [
        'http://www.w3.org/2001/xml.xsd' => (
            __DIR__ . '/Resources/xml.xsd'
        ),
        'http://www.w3.org/2001/XMLSchema.xsd' => (
            __DIR__ . '/Resources/XMLSchema.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd' => (
            __DIR__ . '/Resources/oasis-200401-wss-wssecurity-secext-1.0.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd' => (
            __DIR__ . '/Resources/oasis-200401-wss-wssecurity-utility-1.0.xsd'
        ),
        'https://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__ . '/Resources/xmldsig-core-schema.xsd'
        ),
        'http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__ . '/Resources/xmldsig-core-schema.xsd'
        ),
    ];

    /**
     * @var string[]
     */
    protected array $knownNamespaceSchemaLocations = [
        'http://www.w3.org/2000/09/xmldsig#' => (
            __DIR__ . '/Resources/xmldsig-core-schema.xsd'
        ),
    ];

    /**
     * @var string[]
     */
    protected static array $globalSchemaInfo = [
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd',
    ];

    private function extractErrorMessage(): \Exception
    {
        $errors = [];

        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf("Error[%s] code %s: %s in '%s' at position %s:%s", $error->level, $error->code, trim($error->message), $error->file, $error->line, $error->column);
        }
        $e = new \Exception(implode('; ', $errors));
        libxml_use_internal_errors(false);

        return $e;
    }

    public function __construct(?DocumentationReader $documentationReader = null)
    {
        if (null === $documentationReader) {
            $documentationReader = new StandardDocumentationReader();
        }
        $this->documentationReader = $documentationReader;
    }

    /**
     * Override remote location with a local file.
     *
     * @param string $remote remote schema URL
     * @param string $local local file path
     */
    public function addKnownSchemaLocation(string $remote, string $local): void
    {
        $this->knownLocationSchemas[$remote] = $local;
    }

    /**
     * Specify schema location by namespace.
     * This can be used for schemas which import namespaces but do not specify schemaLocation attributes.
     *
     * @param string $namespace namespace
     * @param string $location schema URL
     */
    public function addKnownNamespaceSchemaLocation(string $namespace, string $location): void
    {
        $this->knownNamespaceSchemaLocations[$namespace] = $location;
    }

    private function loadAttributeGroup(
        Schema $schema,
        \DOMElement $node,
    ): \Closure {
        $attGroup = new AttributeGroup($schema, $node->getAttribute('name'));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schema, $node, $attGroup): void {
            self::againstDOMNodeList(
                $node,
                function (\DOMElement $node, \DOMElement $childNode) use ($schema, $attGroup): void {
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

    private function getAttributeFromAttributeOrRef(
        \DOMElement $childNode,
        Schema $schema,
        \DOMElement $node,
    ): AttributeItem {
        if ($childNode->hasAttribute('ref')) {
            $attributeDef = $this->findAttributeItem($schema, $node, $childNode->getAttribute('ref'));
            if ($attributeDef instanceof AttributeDef) {
                $attribute = new AttributeRef($attributeDef);
                $attribute->setDoc($this->getDocumentation($childNode));
                $this->fillAttribute($attribute, $childNode);

                if ($node->hasAttribute('name')) {
                    $attribute->setName($node->getAttribute('name'));
                }
            } else {
                $attribute = $attributeDef;
            }
        } else {
            /**
             * @var Attribute
             */
            $attribute = $this->createAttribute($schema, $childNode);
        }

        return $attribute;
    }

    private function createAttribute(Schema $schema, \DOMElement $node): Attribute
    {
        $attribute = new Attribute($schema, $node->getAttribute('name'));
        $attribute->setDoc($this->getDocumentation($node));
        $this->fillItem($attribute, $node);
        $this->fillAttribute($attribute, $node);

        return $attribute;
    }

    private function fillAttribute(AttributeSingle $attribute, \DOMElement $node): void
    {
        if ($node->hasAttribute('fixed')) {
            $attribute->setFixed($node->getAttribute('fixed'));
        }
        if ($node->hasAttribute('default')) {
            $attribute->setDefault($node->getAttribute('default'));
        }
        if ($node->hasAttribute('nillable')) {
            $attribute->setNil('true' === $node->getAttribute('nillable'));
        }

        $attribute->setQualified(
            $node->hasAttribute('form')
                ? 'qualified' === $node->getAttribute('form')
                : $attribute->getSchema()->getAttributesQualification()
        );

        if ($node->hasAttribute('use')) {
            $attribute->setUse($node->getAttribute('use'));
        }

        $attribute->setCustomAttributes($this->loadCustomAttributesForElement($attribute, $node));
    }

    /**
     * @return list<CustomAttribute>
     */
    private function loadCustomAttributesForElement(SchemaItem $item, \DOMElement $node): array
    {
        $customAttributes = [];
        foreach ($node->attributes as $attr) {
            if (null !== $attr->namespaceURI && self::XSD_NS !== $attr->namespaceURI) {
                $customAttributes[] = new CustomAttribute(
                    $attr->namespaceURI,
                    $attr->name,
                    $attr->value
                );
            }
        }

        return $customAttributes;
    }

    private function loadAttributeOrElementDef(
        Schema $schema,
        \DOMElement $node,
        \DOMElement $childNode,
        bool $isAttribute,
    ): \Closure {
        $name = $childNode->getAttribute('name');
        if ($isAttribute) {
            $attribute = new AttributeDef($schema, $name);
            $attribute->setDoc($this->getDocumentation($childNode));
            $this->fillAttribute($attribute, $childNode);
            $schema->addAttribute($attribute);
        } else {
            $attribute = new ElementDef($schema, $name);
            $attribute->setDoc($this->getDocumentation($childNode));
            $this->fillElement($attribute, $childNode);
            $schema->addElement($attribute);
        }

        return function () use ($attribute, $childNode, $node): void {
            $this->fillItem($attribute, $childNode, $node);
        };
    }

    private function loadElementDef(Schema $schema, \DOMElement $node, \DOMElement $childNode): \Closure
    {
        return $this->loadAttributeOrElementDef($schema, $node, $childNode, false);
    }

    private function loadAttributeDef(Schema $schema, \DOMElement $node, \DOMElement $childNode): \Closure
    {
        return $this->loadAttributeOrElementDef($schema, $node, $childNode, true);
    }

    private function getDocumentation(\DOMElement $node): string
    {
        return $this->documentationReader->get($node);
    }

    /**
     * @return \Closure[]
     */
    private function schemaNode(Schema $schema, \DOMElement $node, ?Schema $parent = null): array
    {
        $this->setSchemaThingsFromNode($schema, $node, $parent);
        $functions = [];

        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($schema, &$functions): void {
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
                        $callback = $this->loadElementDef($schema, $node, $childNode);
                        break;
                    case 'attribute':
                        $callback = $this->loadAttributeDef($schema, $node, $childNode);
                        break;
                    case 'group':
                        $callback = $this->loadGroup($schema, $childNode);
                        break;
                    case 'choice':
                        $callback = $this->loadChoice($schema, $childNode);
                        break;
                    case 'complexType':
                        $callback = $this->loadComplexType($schema, $childNode);
                        break;
                    case 'simpleType':
                        $callback = $this->loadSimpleType($schema, $childNode);
                        break;
                }

                if ($callback instanceof \Closure) {
                    $functions[] = $callback;
                }
            }
        );

        return $functions;
    }

    private function createGroupRef(Group $referenced, \DOMElement $node): GroupRef
    {
        $ref = new GroupRef($referenced);
        $ref->setDoc($this->getDocumentation($node));

        self::maybeSetMax($ref, $node);
        self::maybeSetMin($ref, $node);

        return $ref;
    }

    private static function maybeSetMax(InterfaceSetMinMax $ref, \DOMElement $node): void
    {
        if ($node->hasAttribute('maxOccurs')) {
            $ref->setMax('unbounded' === $node->getAttribute('maxOccurs') ? -1 : (int) $node->getAttribute('maxOccurs'));
        }
    }

    private static function maybeSetMin(InterfaceSetMinMax $ref, \DOMElement $node): void
    {
        if ($node->hasAttribute('minOccurs')) {
            $ref->setMin((int) $node->getAttribute('minOccurs'));
            if (-1 !== $ref->getMax() && $ref->getMin() > $ref->getMax()) {
                $ref->setMax($ref->getMin());
            }
        }
    }

    private static function maybeSetFixed(InterfaceSetFixed $ref, \DOMElement $node): void
    {
        if ($node->hasAttribute('fixed')) {
            $ref->setFixed($node->getAttribute('fixed'));
        }
    }

    private static function maybeSetDefault(InterfaceSetDefault $ref, \DOMElement $node): void
    {
        if ($node->hasAttribute('default')) {
            $ref->setDefault($node->getAttribute('default'));
        }
    }

    private static function maybeSetAbstract(InterfaceSetAbstract $ref, \DOMElement $node): void
    {
        if ($node->hasAttribute('abstract')) {
            $ref->setAbstract(in_array($node->getAttribute('abstract'), ['true', '1'], true));
        }
    }

    private function loadSequence(ElementContainer $elementContainer, \DOMElement $node, ?int $max = null, ?int $min = null): void
    {
        $min = $this->loadMinFromNode($node, $min);
        $max = $this->loadMaxFromNode($node, $max);

        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($elementContainer, $max, $min): void {
                $this->loadSequenceChildNode(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max,
                    $min
                );
            }
        );
    }

    private function loadMinFromNode(\DOMElement $node, ?int $min): ?int
    {
        if (null === $min && !$node->hasAttribute('minOccurs')) {
            return null;
        }

        $minOccurs = (int) $node->getAttribute('minOccurs');

        // NOTE must not pass "null" to max() function, because "max(null, 0)" returns "null" instead of "0"
        return max((int) $min, $minOccurs);
    }

    private function loadMaxFromNode(\DOMElement $node, ?int $max): ?int
    {
        $maxOccurs = $node->getAttribute('maxOccurs');

        if ('unbounded' === $maxOccurs) {
            return -1;
        }

        if (is_numeric($maxOccurs)) {
            $maxOccurs = (int) $maxOccurs;

            if (null !== $max) {
                return min($max, $maxOccurs);
            }

            if (0 < $maxOccurs) {
                return $maxOccurs;
            }
        }

        return $max;
    }

    private function loadSequenceChildNode(
        ElementContainer $elementContainer,
        \DOMElement $node,
        \DOMElement $childNode,
        ?int $max,
        ?int $min = null,
    ): void {
        switch ($childNode->localName) {
            case 'sequence':
            case 'all':
                $sequence = null;
                if ($elementContainer instanceof Choice) {
                    // add sequence explicitly to avoid competing sequences within the choice
                    $sequence = $this->createSequence($elementContainer->getSchema(), $node);
                    $elementContainer->addElement($sequence);
                }
                $this->loadSequence(
                    $sequence ?? $elementContainer,
                    $childNode,
                    $max,
                    $min
                );
                break;
            case 'choice':
                $this->loadChoiceWithChildren($elementContainer->getSchema(), $childNode, $elementContainer, $max, $min);
                break;
            case 'element':
                $this->loadSequenceChildNodeLoadElement(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max,
                    $min
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
            case 'any':
                $this->loadSequenceChildNodeLoadAny(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max,
                    $min
                );
                break;
        }
    }

    private function loadSequenceChildNodeLoadElement(
        ElementContainer $elementContainer,
        \DOMElement $node,
        \DOMElement $childNode,
        ?int $max,
        ?int $min,
    ): void {
        $schema = $elementContainer->getSchema();
        if ($childNode->hasAttribute('ref')) {
            $elementDef = $this->findElement($schema, $node, $childNode->getAttribute('ref'));
            $element = new ElementRef($elementDef);
            $element->setDoc($this->getDocumentation($childNode));
            $this->fillElement($element, $childNode);

            if ($node->hasAttribute('name')) {
                $element->setName($node->getAttribute('name'));
            }
        } else {
            $element = $this->createElement($schema, $childNode);
        }

        $this->resolveSubstitutionGroup($schema, $node, $childNode, $element);

        if (null !== $min) {
            $element->setMin($min);
        }

        if (null !== $max && 0 !== $max) {
            $element->setMax(-1);
        }
        $elementContainer->addElement($element);
    }

    private function loadSequenceChildNodeLoadAny(
        ElementContainer $elementContainer,
        \DOMElement $node,
        \DOMElement $childNode,
        ?int $max,
        ?int $min,
    ): void {
        $schema = $elementContainer->getSchema();
        $element = $this->createAnyElement($schema, $childNode);

        if (null !== $min) {
            $element->setMin($min);
        }

        if (null !== $max && 1 < $max) {
            $element->setMax($max);
        }
        $elementContainer->addElement($element);
    }

    private function resolveSubstitutionGroup(
        Schema $schema,
        \DOMElement $node,
        \DOMElement $childNode,
        AbstractElementSingle $element,
    ): void {
        if ($childNode->hasAttribute('substitutionGroup')) {
            $substitutionGroup = $childNode->getAttribute('substitutionGroup');
            $elementDef = $this->findElement($schema, $node, $substitutionGroup);
            $elementDef->addSubstitutionCandidate($element);
        }
    }

    private function addGroupAsElement(
        Schema $schema,
        \DOMElement $node,
        \DOMElement $childNode,
        ElementContainer $elementContainer,
    ): void {
        $referencedGroup = $this->findGroup(
            $schema,
            $node,
            $childNode->getAttribute('ref')
        );

        $group = $this->createGroupRef($referencedGroup, $childNode);
        $elementContainer->addElement($group);
    }

    private function loadChoiceWithChildren(
        Schema $schema,
        \DOMElement $node,
        ElementContainer $elementContainer,
        ?int $max = null,
        ?int $min = null,
    ): void {
        $choice = $this->createChoice($schema, $node);
        $elementContainer->addElement($choice);

        $min = $this->loadMinFromNode($node, $min);
        $max = $this->loadMaxFromNode($node, $max);

        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($choice, $max, $min): void {
                $this->loadSequenceChildNode(
                    $choice,
                    $node,
                    $childNode,
                    $max,
                    $min
                );
            }
        );
    }

    private function loadGroup(Schema $schema, \DOMElement $node): \Closure
    {
        $group = new Group($schema, $node->getAttribute('name'));
        $group->setDoc($this->getDocumentation($node));
        $groupOriginal = $group;

        if ($node->hasAttribute('maxOccurs') || $node->hasAttribute('minOccurs')) {
            $group = $this->createGroupRef($group, $node);
        }

        $schema->addGroup($group);

        return function () use ($groupOriginal, $node): void {
            self::againstDOMNodeList(
                $node,
                function (\DOMElement $node, \DOMElement $childNode) use ($groupOriginal): void {
                    switch ($childNode->localName) {
                        case 'sequence':
                        case 'all':
                            $this->loadSequence($groupOriginal, $childNode);
                            break;
                        case 'choice':
                            $this->loadChoiceWithChildren($groupOriginal->getSchema(), $childNode, $groupOriginal);
                    }
                }
            );
        };
    }

    private function loadChoice(Schema $schema, \DOMElement $node): \Closure
    {
        $choice = $this->createChoice($schema, $node);

        return function () use ($choice, $node): void {
            self::againstDOMNodeList(
                $node,
                function (\DOMElement $node, \DOMElement $childNode) use ($choice): void {
                    $this->loadSequenceChildNode(
                        $choice,
                        $node,
                        $childNode,
                        null,
                        null
                    );
                }
            );
        };
    }

    private function createChoice(Schema $schema, \DOMElement $node): Choice
    {
        $choice = new Choice($schema, '');
        $choice->setDoc($this->getDocumentation($node));
        self::maybeSetMax($choice, $node);
        self::maybeSetMin($choice, $node);

        return $choice;
    }

    private function createSequence(Schema $schema, \DOMElement $node): Sequence
    {
        $sequence = new Sequence($schema, '');
        $sequence->setDoc($this->getDocumentation($node));
        self::maybeSetMax($sequence, $node);
        self::maybeSetMin($sequence, $node);

        return $sequence;
    }

    private function loadComplexType(Schema $schema, \DOMElement $node, ?\Closure $callback = null): \Closure
    {
        /**
         * @var bool
         */
        $isSimple = false;

        self::againstDOMNodeList(
            $node,
            static function (\DOMElement $node, \DOMElement $childNode) use (&$isSimple): void {
                if ($isSimple) {
                    return;
                }
                if ('simpleContent' === $childNode->localName) {
                    $isSimple = true;
                }
            }
        );

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute('name')) : new ComplexType($schema, $node->getAttribute('name'));

        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $schema, $callback): void {
            $this->fillTypeNode($type, $node, true);

            self::againstDOMNodeList(
                $node,
                function (\DOMElement $node, \DOMElement $childNode) use ($schema, $type): void {
                    $this->loadComplexTypeFromChildNode($type, $node, $childNode, $schema);
                }
            );

            if ($callback instanceof \Closure) {
                call_user_func($callback, $type);
            }
        };
    }

    private function loadComplexTypeFromChildNode(
        BaseComplexType $type,
        \DOMElement $node,
        \DOMElement $childNode,
        Schema $schema,
    ): void {
        switch ($childNode->localName) {
            case 'sequence':
            case 'all':
                if ($type instanceof ElementContainer) {
                    $this->loadSequence($type, $childNode);
                }
                break;
            case 'choice':
                if ($type instanceof ComplexType) {
                    $this->loadChoiceWithChildren($schema, $childNode, $type);
                }
                break;
            case 'attribute':
                $this->addAttributeFromAttributeOrRef($type, $childNode, $schema, $node);
                break;
            case 'attributeGroup':
                $this->findSomethingLikeAttributeGroup($schema, $node, $childNode, $type);
                break;
            case 'group':
                if ($type instanceof ComplexType) {
                    $this->addGroupAsElement($schema, $node, $childNode, $type);
                }
                break;
        }
    }

    private function loadSimpleType(Schema $schema, \DOMElement $node, ?\Closure $callback = null): \Closure
    {
        $type = new SimpleType($schema, $node->getAttribute('name'));
        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return function () use ($type, $node, $callback): void {
            $this->fillTypeNode($type, $node, true);

            self::againstDOMNodeList(
                $node,
                function (\DOMElement $node, \DOMElement $childNode) use ($type): void {
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

            if ($callback instanceof \Closure) {
                call_user_func($callback, $type);
            }
        };
    }

    private function loadList(SimpleType $type, \DOMElement $node): void
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
                function (\DOMElement $node, \DOMElement $childNode) use ($type): void {
                    $this->loadTypeWithCallback(
                        $type->getSchema(),
                        $childNode,
                        function (SimpleType $list) use ($type): void {
                            $type->setList($list);
                        }
                    );
                }
            );
        }
    }

    private function findSomeType(
        SchemaItem $fromThis,
        \DOMElement $node,
        string $attributeName,
    ): SchemaItem {
        return $this->findSomeTypeFromAttribute(
            $fromThis,
            $node,
            $node->getAttribute($attributeName)
        );
    }

    private function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        \DOMElement $node,
        string $attributeName,
    ): SchemaItem {
        return $this->findType(
            $fromThis->getSchema(),
            $node,
            $attributeName
        );
    }

    private function loadUnion(SimpleType $type, \DOMElement $node): void
    {
        if ($node->hasAttribute('memberTypes')) {
            $types = preg_split('/\s+/', $node->getAttribute('memberTypes'));
            foreach ($types as $typeName) {
                /**
                 * @var SimpleType
                 */
                $unionType = $this->findSomeTypeFromAttribute($type, $node, $typeName);
                $type->addUnion($unionType);
            }
        }
        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($type): void {
                $this->loadTypeWithCallback(
                    $type->getSchema(),
                    $childNode,
                    function (SimpleType $unType) use ($type): void {
                        $type->addUnion($unType);
                    }
                );
            }
        );
    }

    private function fillTypeNode(Type $type, \DOMElement $node, bool $checkAbstract = false): void
    {
        if ($checkAbstract) {
            self::maybeSetAbstract($type, $node);
        }
        if ($type instanceof ComplexType) {
            if ($node->hasAttribute('mixed')) {
                $type->setMixed(in_array($node->getAttribute('mixed'), ['true', '1'], true));
            }
        }

        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($type): void {
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

    private function loadExtension(BaseComplexType $type, \DOMElement $node): void
    {
        $extension = new Extension();
        $type->setExtension($extension);

        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase($type, $extension, $node);
        }
        $this->loadExtensionChildNodes($type, $node);
    }

    private function findAndSetSomeBase(Type $type, Base $setBaseOnThis, \DOMElement $node): void
    {
        /**
         * @var Type
         */
        $parent = $this->findSomeType($type, $node, 'base');
        $setBaseOnThis->setBase($parent);
    }

    private function loadExtensionChildNodes(
        BaseComplexType $type,
        \DOMElement $node,
    ): void {
        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($type): void {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choice':
                    case 'all':
                        if ($type instanceof ElementContainer) {
                            $this->loadSequence($type, $childNode);
                        }
                        break;
                }
                $this->loadChildAttributesAndAttributeGroups($type, $node, $childNode);
            }
        );
    }

    private function loadChildAttributesAndAttributeGroups(
        BaseComplexType $type,
        \DOMElement $node,
        \DOMElement $childNode,
    ): void {
        switch ($childNode->localName) {
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

    private function loadRestriction(Type $type, \DOMElement $node): void
    {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase($type, $restriction, $node);
        } else {
            self::againstDOMNodeList(
                $node,
                function (
                    \DOMElement $node,
                    \DOMElement $childNode,
                ) use (
                    $type,
                    $restriction
                ): void {
                    $this->loadTypeWithCallback(
                        $type->getSchema(),
                        $childNode,
                        function (Type $restType) use ($restriction): void {
                            $restriction->setBase($restType);
                        }
                    );
                }
            );
        }
        $this->loadRestrictionChildNodes($type, $restriction, $node);
    }

    private function loadRestrictionChildNodes(
        Type $type,
        Restriction $restriction,
        \DOMElement $node,
    ): void {
        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($type, $restriction): void {
                if ($type instanceof BaseComplexType) {
                    $this->loadChildAttributesAndAttributeGroups($type, $node, $childNode);
                }

                if ($type instanceof ElementContainer) {
                    $this->loadSequenceChildNode($type, $node, $childNode, null, null);
                }

                if (null !== ($restrictionType = RestrictionType::tryFrom($childNode->localName))) {
                    $restriction->addCheck(
                        $restrictionType,
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
     * @return mixed[]
     */
    private static function splitParts(\DOMElement $node, string $typeName): array
    {
        $prefix = null;
        $name = $typeName;
        if (str_contains($typeName, ':')) {
            [$prefix, $name] = explode(':', $typeName);
        }

        // Get namespace URI for prefix. If prefix is null, it will return the default namespace
        $namespace = $node->lookupNamespaceUri($prefix);

        // If no namespace is found, throw an exception only if a prefix was provided.
        // If no prefix was provided and the above lookup failed, this means that there
        // was no defalut namespace defined, making the element part of no namespace.
        // In this case, we should not throw an exception since this is valid xml.
        if (!$namespace && null !== $prefix) {
            throw new TypeException(sprintf("Can't find namespace for prefix '%s', at line %d in %s ", $prefix, $node->getLineNo(), $node->ownerDocument->documentURI));
        }

        return [
            $name,
            $namespace,
            $prefix,
        ];
    }

    private function findAttributeItem(Schema $schema, \DOMElement $node, string $typeName): AttributeItem
    {
        [$name, $namespace] = self::splitParts($node, $typeName);

        try {
            /**
             * @var AttributeItem $out
             */
            $out = $schema->findAttribute((string) $name, $namespace);

            return $out;
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", 'attribute', $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    private function findAttributeGroup(Schema $schema, \DOMElement $node, string $typeName): AttributeGroup
    {
        [$name, $namespace] = self::splitParts($node, $typeName);

        try {
            /**
             * @var AttributeGroup $out
             */
            $out = $schema->findAttributeGroup((string) $name, $namespace);

            return $out;
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", 'attributegroup', $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    private function findElement(Schema $schema, \DOMElement $node, string $typeName): ElementDef
    {
        [$name, $namespace] = self::splitParts($node, $typeName);

        try {
            return $schema->findElement((string) $name, $namespace);
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", 'element', $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    private function findGroup(Schema $schema, \DOMElement $node, string $typeName): Group
    {
        [$name, $namespace] = self::splitParts($node, $typeName);

        try {
            /**
             * @var Group $out
             */
            $out = $schema->findGroup((string) $name, $namespace);

            return $out;
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", 'group', $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    private function findType(Schema $schema, \DOMElement $node, string $typeName): SchemaItem
    {
        [$name, $namespace] = self::splitParts($node, $typeName);

        $tryFindType = static function (Schema $schema, string $name, ?string $namespace): ?SchemaItem {
            try {
                return $schema->findType($name, $namespace);
            } catch (TypeNotFoundException $e) {
                return null;
            }
        };

        $interestingSchemas = array_merge([$schema], $this->loadedSchemas[$namespace] ?? []);
        foreach ($interestingSchemas as $interestingSchema) {
            if ($result = $tryFindType($interestingSchema, $name, $namespace)) {
                return $result;
            }
        }

        throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", 'type', $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI));
    }

    private function fillItem(Item $element, \DOMElement $node, ?\DOMElement $parentNode = null): void
    {
        if ($element instanceof ElementDef) {
            $this->resolveSubstitutionGroup($element->getSchema(), $parentNode, $node, $element);
        }

        /**
         * @var bool
         */
        $skip = false;
        self::againstDOMNodeList(
            $node,
            function (\DOMElement $node, \DOMElement $childNode) use ($element, &$skip): void {
                if (
                    !$skip
                    && in_array(
                        $childNode->localName,
                        [
                            'complexType',
                            'simpleType',
                        ],
                        true
                    )
                ) {
                    $this->loadTypeWithCallback(
                        $element->getSchema(),
                        $childNode,
                        function (Type $type) use ($element): void {
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

    private function fillItemNonLocalType(Item $element, \DOMElement $node): void
    {
        if ($node->getAttribute('type')) {
            /**
             * @var Type
             */
            $type = $this->findSomeType($element, $node, 'type');
        } else {
            $prefix = $node->lookupPrefix(self::XSD_NS);
            if ($prefix) {
                $prefix .= ':';
            }
            /**
             * @var Type
             */
            $type = $this->findSomeTypeFromAttribute(
                $element,
                $node,
                $prefix . 'anyType'
            );
        }

        $element->setType($type);
    }

    private function loadImport(
        Schema $schema,
        \DOMElement $node,
    ): \Closure {
        $namespace = $node->getAttribute('namespace');
        $schemaLocation = $node->getAttribute('schemaLocation');
        if (!$schemaLocation && isset($this->knownNamespaceSchemaLocations[$namespace])) {
            $schemaLocation = $this->knownNamespaceSchemaLocations[$namespace];
        }

        // postpone schema loading
        if ($namespace && !$schemaLocation && !isset(self::$globalSchemaInfo[$namespace])) {
            return function () use ($schema, $namespace): void {
                if (!empty($this->loadedSchemas[$namespace])) {
                    foreach ($this->loadedSchemas[$namespace] as $s) {
                        $schema->addSchema($s, $namespace);
                    }
                }
            };
        }

        if ($namespace && !$schemaLocation && !empty($this->loadedSchemas[$namespace])) {
            foreach ($this->loadedSchemas[$namespace] as $s) {
                $schema->addSchema($s, $namespace);
            }
        }

        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $schemaLocation);

        if (isset($this->loadedFiles[$file])) {
            $schema->addSchema($this->loadedFiles[$file]);

            return function (): void {
            };
        }

        return $this->loadImportFresh($namespace, $schema, $file);
    }

    private function createOrUseSchemaForNs(Schema $schema, string $namespace): Schema
    {
        if ('' !== trim($namespace)) {
            $newSchema = new Schema();
            $newSchema->addSchema($this->getGlobalSchema());
            $schema->addSchema($newSchema);
        } else {
            $newSchema = $schema;
        }

        return $newSchema;
    }

    private function loadImportFresh(
        string $namespace,
        Schema $schema,
        string $file,
    ): \Closure {
        return function () use ($namespace, $schema, $file): void {
            $dom = $this->getDOM(
                $this->knownLocationSchemas[$file]
                    ?? $file
            );

            $schemaNew = $this->createOrUseSchemaForNs($schema, $namespace);

            $this->setLoadedFile($file, $schemaNew);

            $callbacks = $this->schemaNode($schemaNew, $dom->documentElement, $schema);

            foreach ($callbacks as $callback) {
                $callback();
            }
        };
    }

    /**
     * @var Schema|null
     */
    protected $globalSchema;

    public function getGlobalSchema(): Schema
    {
        if (!($this->globalSchema instanceof Schema)) {
            $callbacks = [];
            $globalSchemas = [];
            /**
             * @var string $namespace
             */
            foreach (self::$globalSchemaInfo as $namespace => $uri) {
                $this->setLoadedFile(
                    $uri,
                    $globalSchemas[$namespace] = $schema = new Schema()
                );
                $this->setLoadedSchema($namespace, $schema);
                if (self::XSD_NS === $namespace) {
                    $this->globalSchema = $schema;
                }
                $xml = $this->getDOM($this->knownLocationSchemas[$uri]);
                $callbacks = array_merge($callbacks, $this->schemaNode($schema, $xml->documentElement));
            }

            $globalSchemas[(string) static::XSD_NS]->addType(new SimpleType($globalSchemas[(string) static::XSD_NS], 'anySimpleType'));
            $globalSchemas[(string) static::XSD_NS]->addType(new SimpleType($globalSchemas[(string) static::XSD_NS], 'anyType'));

            $globalSchemas[(string) static::XML_NS]->addSchema(
                $globalSchemas[(string) static::XSD_NS],
                (string) static::XSD_NS
            );
            $globalSchemas[(string) static::XSD_NS]->addSchema(
                $globalSchemas[(string) static::XML_NS],
                (string) static::XML_NS
            );

            /**
             * @var \Closure $callback
             */
            foreach ($callbacks as $callback) {
                $callback();
            }
        }

        if (!($this->globalSchema instanceof Schema)) {
            throw new TypeException('Global schema not discovered');
        }

        return $this->globalSchema;
    }

    /**
     * @param \DOMNode[] $nodes
     */
    public function readNodes(array $nodes, ?string $file = null): Schema
    {
        $rootSchema = new Schema();
        $rootSchema->addSchema($this->getGlobalSchema());

        if (null !== $file) {
            $this->setLoadedFile($file, $rootSchema);
        }

        $all = [];
        foreach ($nodes as $k => $node) {
            if (($node instanceof \DOMElement) && self::XSD_NS === $node->namespaceURI && 'schema' == $node->localName) {
                $holderSchema = new Schema();
                $holderSchema->addSchema($this->getGlobalSchema());

                $this->setLoadedSchemaFromElement($node, $holderSchema);

                $rootSchema->addSchema($holderSchema);

                $callbacks = $this->schemaNode($holderSchema, $node);
                $all = array_merge($callbacks, $all);
            }
        }

        foreach ($all as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }

    public function readNode(\DOMElement $node, ?string $file = null): Schema
    {
        $rootSchema = new Schema();
        $rootSchema->addSchema($this->getGlobalSchema());

        if (null !== $file) {
            $this->setLoadedFile($file, $rootSchema);
        }

        $this->setLoadedSchemaFromElement($node, $rootSchema);

        $callbacks = $this->schemaNode($rootSchema, $node);

        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }

    /**
     * @throws IOException
     */
    public function readString(string $content, string $file = 'schema.xsd'): Schema
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        if (!@$xml->loadXML($content)) {
            throw new IOException("Can't load the schema", 0, $this->extractErrorMessage());
        }
        libxml_use_internal_errors(false);
        $xml->documentURI = $file;

        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @throws IOException
     */
    public function readFile(string $file): Schema
    {
        $xml = $this->getDOM($file);

        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @throws IOException
     */
    private function getDOM(string $file): \DOMDocument
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        if (!@$xml->load($file)) {
            libxml_use_internal_errors(false);
            throw new IOException("Can't load the file '$file'", 0, $this->extractErrorMessage());
        }
        libxml_use_internal_errors(false);

        return $xml;
    }

    private static function againstDOMNodeList(\DOMElement $node, \Closure $againstNodeList): void
    {
        $limit = $node->childNodes->length;
        for ($i = 0; $i < $limit; ++$i) {
            /**
             * @var \DOMNode
             */
            $childNode = $node->childNodes->item($i);

            if ($childNode instanceof \DOMElement) {
                $againstNodeList($node, $childNode);
            }
        }
    }

    private function loadTypeWithCallback(Schema $schema, \DOMElement $childNode, \Closure $callback): void
    {
        /**
         * @var \Closure|null $func
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

        if ($func instanceof \Closure) {
            call_user_func($func);
        }
    }

    private function createElement(Schema $schema, \DOMElement $node): Element
    {
        $element = new Element($schema, $node->getAttribute('name'));
        $element->setDoc($this->getDocumentation($node));
        $this->fillItem($element, $node);
        $this->fillElement($element, $node);

        return $element;
    }

    private function fillElement(AbstractElementSingle $element, \DOMElement $node): void
    {
        self::maybeSetMax($element, $node);
        self::maybeSetMin($element, $node);
        self::maybeSetFixed($element, $node);
        self::maybeSetDefault($element, $node);
        self::maybeSetAbstract($element, $node);

        $xp = new \DOMXPath($node->ownerDocument);
        $xp->registerNamespace('xs', self::XSD_NS);

        if ($xp->query('ancestor::xs:choice', $node)->length) {
            $element->setMin(0);
        }

        if ($node->hasAttribute('nillable')) {
            $element->setNil('true' === $node->getAttribute('nillable'));
        }

        $element->setQualified(
            $node->hasAttribute('form')
                ? 'qualified' === $node->getAttribute('form')
                : $element->getSchema()->getElementsQualification()
        );

        $parentNode = $node->parentNode;
        if ('schema' !== $parentNode->localName || self::XSD_NS !== $parentNode->namespaceURI) {
            if ($element instanceof ElementRef) {
                $element->setLocal($element->getReferencedElement()->isLocal());
            } else {
                $element->setLocal(true);
            }
        }

        $element->setCustomAttributes($this->loadCustomAttributesForElement($element, $node));
    }

    private function createAnyElement(Schema $schema, \DOMElement $node): Any
    {
        $element = new Any($schema);
        $this->fillAnyElement($element, $node);

        return $element;
    }

    private function fillAnyElement(Any $element, \DOMElement $node): void
    {
        self::maybeSetMax($element, $node);
        self::maybeSetMin($element, $node);

        if ($node->hasAttribute('id')) {
            $element->setId($node->getAttribute('id'));
        }

        if ($node->hasAttribute('namespace')) {
            $element->setNamespace($node->getAttribute('namespace'));
        }

        if ($node->hasAttribute('processContents')) {
            $element->setProcessContents(
                ProcessContents::tryFrom($node->getAttribute('processContents')) ?? ProcessContents::default()
            );
        }

        $element->setDoc($this->getDocumentation($node));
    }

    private function addAttributeFromAttributeOrRef(
        BaseComplexType $type,
        \DOMElement $childNode,
        Schema $schema,
        \DOMElement $node,
    ): void {
        $attribute = $this->getAttributeFromAttributeOrRef(
            $childNode,
            $schema,
            $node
        );

        $type->addAttribute($attribute);
    }

    private function findSomethingLikeAttributeGroup(
        Schema $schema,
        \DOMElement $node,
        \DOMElement $childNode,
        AttributeContainer $addToThis,
    ): void {
        $attribute = $this->findAttributeGroup($schema, $node, $childNode->getAttribute('ref'));
        $addToThis->addAttribute($attribute);
    }

    private function setLoadedFile(string $key, Schema $schema): void
    {
        $this->loadedFiles[$key] = $schema;
    }

    private function setLoadedSchemaFromElement(\DOMElement $node, Schema $schema): void
    {
        if ($node->hasAttribute('targetNamespace')) {
            $this->setLoadedSchema($node->getAttribute('targetNamespace'), $schema);
        }
    }

    private function setLoadedSchema(string $namespace, Schema $schema): void
    {
        if (!isset($this->loadedSchemas[$namespace])) {
            $this->loadedSchemas[$namespace] = [];
        }
        if (!in_array($schema, $this->loadedSchemas[$namespace], true)) {
            $this->loadedSchemas[$namespace][] = $schema;
        }
    }

    private function setSchemaThingsFromNode(
        Schema $schema,
        \DOMElement $node,
        ?Schema $parent = null,
    ): void {
        $schema->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute('targetNamespace')) {
            $schema->setTargetNamespace($node->getAttribute('targetNamespace'));
        } elseif ($parent instanceof Schema) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }
        $schema->setElementsQualification('qualified' == $node->getAttribute('elementFormDefault'));
        $schema->setAttributesQualification('qualified' == $node->getAttribute('attributeFormDefault'));
        $schema->setDoc($this->getDocumentation($node));
    }
}
