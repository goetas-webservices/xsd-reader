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

class SchemaReader extends SchemaReaderFindAbstraction
{
    /**
    * @return Closure
    */
    protected function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        return AttributeGroup::loadAttributeGroup($this, $schema, $node);
    }

    /**
    * @param bool $attributeDef
    *
    * @return Closure
    */
    protected function loadAttributeOrElementDef(
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
    protected function loadAttributeDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, true);
    }

    protected function setSchemaThingsFromNode(
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

    /**
    * @param int|null $max
    *
    * @return int|null
    */
    protected static function loadSequenceNormaliseMax(DOMElement $node, $max)
    {
        return
        (
            (is_int($max) && (bool) $max) ||
            $node->getAttribute("maxOccurs") == "unbounded" ||
            $node->getAttribute("maxOccurs") > 1
        )
            ? 2
            : null;
    }

    /**
    * @param int|null $max
    */
    protected function loadSequence(ElementContainer $elementContainer, DOMElement $node, $max = null)
    {
        $max = static::loadSequenceNormaliseMax($node, $max);

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $this->loadSequenceChildNode(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max
                );
            }
        }
    }

    /**
    * @param int|null $max
    */
    protected function loadSequenceChildNode(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        $commonMethods = [
            [
                ['sequence', 'choice', 'all'],
                [$this, 'loadSequenceChildNodeLoadSequence'],
                [
                    $elementContainer,
                    $childNode,
                    $max,
                ],
            ],
        ];
        $methods = [
            'element' => [
                [$this, 'loadSequenceChildNodeLoadElement'],
                [
                    $elementContainer,
                    $node,
                    $childNode,
                    $max
                ]
            ],
            'group' => [
                [$this, 'loadSequenceChildNodeLoadGroup'],
                [
                    $elementContainer,
                    $node,
                    $childNode
                ]
            ],
        ];

        $this->maybeCallCallableWithArgs($childNode, $commonMethods, $methods);
    }

    /**
    * @param int|null $max
    */
    protected function loadSequenceChildNodeLoadSequence(
        ElementContainer $elementContainer,
        DOMElement $childNode,
        $max
    ) {
        $this->loadSequence($elementContainer, $childNode, $max);
    }

    /**
    * @param int|null $max
    */
    protected function loadSequenceChildNodeLoadElement(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        if ($childNode->hasAttribute("ref")) {
            /**
            * @var ElementDef $referencedElement
            */
            $referencedElement = $this->findSomething('findElement', $elementContainer->getSchema(), $node, $childNode->getAttribute("ref"));
            $element = ElementRef::loadElementRef(
                $referencedElement,
                $childNode
            );
        } else {
            $element = Element::loadElement(
                $this,
                $elementContainer->getSchema(),
                $childNode
            );
        }
        if (is_int($max) && (bool) $max) {
            $element->setMax($max);
        }
        $elementContainer->addElement($element);
    }

    protected function loadSequenceChildNodeLoadGroup(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode
    ) {
        $this->addGroupAsElement(
            $elementContainer->getSchema(),
            $node,
            $childNode,
            $elementContainer
        );
    }

    protected function addGroupAsElement(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        ElementContainer $elementContainer
    ) {
        /**
        * @var Group $referencedGroup
        */
        $referencedGroup = $this->findSomething(
            'findGroup',
            $schema,
            $node,
            $childNode->getAttribute("ref")
        );

        $group = GroupRef::loadGroupRef($referencedGroup, $childNode);
        $elementContainer->addElement($group);
    }

    /**
    * @return Closure
    */
    protected function loadGroup(Schema $schema, DOMElement $node)
    {
        return Group::loadGroup($this, $schema, $node);
    }

    /**
    * @return BaseComplexType
    */
    protected function loadComplexTypeBeforeCallbackCallback(
        Schema $schema,
        DOMElement $node
    ) {
        $isSimple = false;

        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName === "simpleContent") {
                $isSimple = true;
                break;
            }
        }

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute("name")) : new ComplexType($schema, $node->getAttribute("name"));

        $type->setDoc(static::getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        return $type;
    }

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    protected function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = $this->loadComplexTypeBeforeCallbackCallback($schema, $node);

        return $this->makeCallbackCallback(
            $type,
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use(
                $schema,
                $type
            ) {
                $this->loadComplexTypeFromChildNode(
                    $type,
                    $node,
                    $childNode,
                    $schema
                );
            },
            $callback
        );
    }

    protected function loadComplexTypeFromChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode,
        Schema $schema
    ) {
        $commonMethods = [
            [
                ['sequence', 'choice', 'all'],
                [$this, 'maybeLoadSequenceFromElementContainer'],
                [
                    $type,
                    $childNode,
                ],
            ],
        ];
        $methods = [
            'attribute' => [
                [$type, 'addAttributeFromAttributeOrRef'],
                [
                    $this,
                    $childNode,
                    $schema,
                    $node
                ]
            ],
            'attributeGroup' => [
                (AttributeGroup::class . '::findSomethingLikeThis'),
                [
                    $this,
                    $schema,
                    $node,
                    $childNode,
                    $type
                ]
            ],
        ];
        if (
            $type instanceof ComplexType
        ) {
            $methods['group'] = [
                [$this, 'addGroupAsElement'],
                [
                    $schema,
                    $node,
                    $childNode,
                    $type
                ]
            ];
        }

        $this->maybeCallCallableWithArgs($childNode, $commonMethods, $methods);
    }

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    protected function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType($schema, $node->getAttribute("name"));
        $type->setDoc(static::getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        static $methods = [
            'union' => 'loadUnion',
            'list' => 'loadList',
        ];

        return $this->makeCallbackCallback(
            $type,
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $methods,
                $type
            ) {
                $this->maybeCallMethod(
                    $methods,
                    $childNode->localName,
                    $childNode,
                    $type,
                    $childNode
                );
            },
            $callback
        );
    }

    protected function loadList(SimpleType $type, DOMElement $node)
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

            Type::loadTypeWithCallbackOnChildNodes(
                $this,
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
    }

    protected function loadUnion(SimpleType $type, DOMElement $node)
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

        Type::loadTypeWithCallbackOnChildNodes(
            $this,
            $type->getSchema(),
            $node,
            $addCallback
        );
    }

    /**
    * @param bool $checkAbstract
    */
    protected function fillTypeNode(Type $type, DOMElement $node, $checkAbstract = false)
    {

        if ($checkAbstract) {
            $type->setAbstract($node->getAttribute("abstract") === "true" || $node->getAttribute("abstract") === "1");
        }

        static $methods = [
            'restriction' => 'loadRestriction',
            'extension' => 'maybeLoadExtensionFromBaseComplexType',
            'simpleContent' => 'fillTypeNode',
            'complexContent' => 'fillTypeNode',
        ];

        foreach ($node->childNodes as $childNode) {
            $this->maybeCallMethod(
                $methods,
                (string) $childNode->localName,
                $childNode,
                $type,
                $childNode
            );
        }
    }

    protected function loadExtensionChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode
    ) {
        $commonMethods = [
            [
                ['sequence', 'choice', 'all'],
                [$this, 'maybeLoadSequenceFromElementContainer'],
                [
                    $type,
                    $childNode,
                ],
            ],
        ];
        $methods = [
            'attribute' => [
                [$type, 'addAttributeFromAttributeOrRef'],
                [
                    $this,
                    $childNode,
                    $type->getSchema(),
                    $node
                ]
            ],
            'attributeGroup' => [
                (AttributeGroup::class . '::findSomethingLikeThis'),
                [
                    $this,
                    $type->getSchema(),
                    $node,
                    $childNode,
                    $type
                ]
            ],
        ];

        $this->maybeCallCallableWithArgs($childNode, $commonMethods, $methods);
    }

    protected function loadExtension(BaseComplexType $type, DOMElement $node)
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

        $this->loadExtensionChildNodes($type, $node->childNodes, $node);
    }

    protected function loadExtensionChildNodes(
        BaseComplexType $type,
        DOMNodeList $childNodes,
        DOMElement $node
    ) {
        foreach ($childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $this->loadExtensionChildNode(
                    $type,
                    $node,
                    $childNode
                );
            }
        }
    }

    protected function loadRestriction(Type $type, DOMElement $node)
    {
        Restriction::loadRestriction($this, $type, $node);
    }

    /**
    * @param string $typeName
    *
    * @return mixed[]
    */
    protected static function splitParts(DOMElement $node, $typeName)
    {
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
    * @return Closure
    */
    protected function loadElementDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, false);
    }

    protected function fillItemNonLocalType(Item $element, DOMElement $node)
    {
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

    /**
    * @return Closure
    */
    protected function loadImport(Schema $schema, DOMElement $node)
    {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute("schemaLocation"));

        $namespace = $node->getAttribute("namespace");

        if (
            (
                isset(self::$globalSchemaInfo[$namespace]) &&
                Schema::hasLoadedFile(
                    $loadedFilesKey = self::$globalSchemaInfo[$namespace]
                )
            ) ||
            Schema::hasLoadedFile(
                $loadedFilesKey = $this->getNamespaceSpecificFileIndex(
                    $file,
                    $namespace
                )
            ) ||
            Schema::hasLoadedFile($loadedFilesKey = $file)
        ) {
            $schema->addSchema(Schema::getLoadedFile($loadedFilesKey));

            return function() {
            };
        }

        return $this->loadImportFresh($schema, $node, $file, $namespace);
    }

    /**
    * @param string $file
    * @param string $namespace
    *
    * @return Closure
    */
    protected function loadImportFresh(
        Schema $schema,
        DOMElement $node,
        $file,
        $namespace
    ) {
        if (! $namespace) {
            $newSchema = Schema::setLoadedFile($file, $schema);
        } else {
            $newSchema = Schema::setLoadedFile($file, new Schema());
            $newSchema->addSchema($this->getGlobalSchema());
        }

        $xml = $this->getDOM(isset($this->knownLocationSchemas[$file]) ? $this->knownLocationSchemas[$file] : $file);

        $callbacks = $this->schemaNode($newSchema, $xml->documentElement, $schema);

        if ($namespace) {
            $schema->addSchema($newSchema);
        }


        return function () use ($callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        };
    }

    /**
    * @return Schema[]
    */
    protected function setupGlobalSchemas(array & $callbacks)
    {
            $globalSchemas = array();
            foreach (self::$globalSchemaInfo as $namespace => $uri) {
                Schema::setLoadedFile(
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
    protected function getNamespaceSpecificFileIndex($file, $targetNamespace)
    {
        return $file . '#' . $targetNamespace;
    }

    /**
     * @param string $file
     *
     * @return DOMDocument
     *
     * @throws IOException
     */
    protected function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->load($file)) {
            throw new IOException("Can't load the file $file");
        }
        return $xml;
    }
}
