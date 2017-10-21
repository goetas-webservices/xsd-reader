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

abstract class AbstractSchemaReader
{

    const XSD_NS = "http://www.w3.org/2001/XMLSchema";

    const XML_NS = "http://www.w3.org/XML/1998/namespace";

    /**
    * @var string[]
    */
    protected $knownLocationSchemas = [
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
    protected static $globalSchemaInfo = array(
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd'
    );

    /**
    * @param string $remote
    * @param string $local
    */
    public function addKnownSchemaLocation($remote, $local)
    {
        $this->knownLocationSchemas[$remote] = $local;
    }

    /**
    * @param string $remote
    *
    * @return bool
    */
    public function hasKnownSchemaLocation($remote)
    {
        return isset($this->knownLocationSchemas[$remote]);
    }

    /**
    * @param string $remote
    *
    * @return string
    */
    public function getKnownSchemaLocation($remote)
    {
        return $this->knownLocationSchemas[$remote];
    }

    /**
    * @return Closure
    */
    abstract protected function loadAttributeGroup(Schema $schema, DOMElement $node);

    /**
    * @param bool $attributeDef
    *
    * @return Closure
    */
    abstract protected function loadAttributeOrElementDef(
        Schema $schema,
        DOMElement $node,
        $attributeDef
    );

    /**
    * @return Closure
    */
    abstract protected function loadAttributeDef(Schema $schema, DOMElement $node);

    /**
     * @param DOMElement $node
     * @return string
     */
    public static function getDocumentation(DOMElement $node)
    {
        $doc = '';
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                & $doc
            ) {
                if ($childNode->localName == "annotation") {
                    $doc .= static::getDocumentation($childNode);
                } elseif ($childNode->localName == 'documentation') {
                    $doc .= (string) $childNode->nodeValue;
                }
            }
        );
        $doc = preg_replace('/[\t ]+/', ' ', $doc);
        return trim($doc);
    }

    /**
    * @param string[] $methods
    * @param string $key
    *
    * @return Closure|null
    */
    public function maybeCallMethod(
        array $methods,
        $key,
        DOMNode $childNode,
        ...$args
    ) {
        if ($childNode instanceof DOMElement && isset($methods[$key])) {
            $method = $methods[$key];

            /**
            * @var Closure|null $append
            */
            $append = $this->$method(...$args);

            if ($append instanceof Closure) {
                return $append;
            }
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param Schema $parent
     * @return Closure[]
     */
    public function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $schema->setSchemaThingsFromNode($node, $parent);
        $functions = array();

        $schemaReaderMethods = [
            'include' => (Schema::class . '::loadImport'),
            'import' => (Schema::class . '::loadImport'),
        ];

        $thisMethods = [
            'element' => [$this, 'loadElementDef'],
            'attribute' => [$this, 'loadAttributeDef'],
            'attributeGroup' => [$this, 'loadAttributeGroup'],
            'group' => [$this, 'loadGroup'],
            'complexType' => [$this, 'loadComplexType'],
            'simpleType' => [$this, 'loadSimpleType'],
        ];

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schemaReaderMethods,
                $schema,
                $thisMethods,
                & $functions
            ) {
                /**
                * @var Closure|null $callback
                */
                $callback = $this->maybeCallCallableWithArgs(
                    $childNode,
                        [],
                        [],
                        [
                            [
                                $schemaReaderMethods,
                                [
                                    $this,
                                    $schema,
                                    $childNode,
                                ]
                            ],
                            [
                                $thisMethods,
                                [
                                    $schema,
                                    $childNode
                                ],
                            ],
                        ]
                );

                if ($callback instanceof Closure) {
                    $functions[] = $callback;
                }
            }
        );

        return $functions;
    }

    /**
    * @return InterfaceSetMinMax
    */
    public static function maybeSetMax(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if (
            $node->hasAttribute("maxOccurs")
        ) {
            $ref->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }

        return $ref;
    }

    /**
    * @return InterfaceSetMinMax
    */
    public static function maybeSetMin(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if ($node->hasAttribute("minOccurs")) {
            $ref->setMin((int) $node->getAttribute("minOccurs"));
        }

        return $ref;
    }

    /**
    * @param int|null $max
    *
    * @return int|null
    */
    abstract protected static function loadSequenceNormaliseMax(DOMElement $node, $max);

    /**
    * @param int|null $max
    */
    abstract protected function loadSequence(ElementContainer $elementContainer, DOMElement $node, $max = null);

    /**
    * @param int|null $max
    */
    abstract protected function loadSequenceChildNode(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    );

    /**
    * @param mixed[][] $methods
    *
    * @return mixed
    */
    abstract protected function maybeCallCallableWithArgs(
        DOMElement $childNode,
        array $commonMethods = [],
        array $methods = [],
        array $commonArguments = []
    );

    /**
    * @param int|null $max
    */
    abstract protected function loadSequenceChildNodeLoadSequence(
        ElementContainer $elementContainer,
        DOMElement $childNode,
        $max
    );

    /**
    * @param int|null $max
    */
    abstract protected function loadSequenceChildNodeLoadElement(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    );

    abstract protected function loadSequenceChildNodeLoadGroup(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode
    );

    abstract protected function addGroupAsElement(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        ElementContainer $elementContainer
    );

    abstract protected function maybeLoadSequenceFromElementContainer(
        BaseComplexType $type,
        DOMElement $childNode
    );

    /**
    * @return Closure
    */
    abstract protected function loadGroup(Schema $schema, DOMElement $node);

    /**
    * @return BaseComplexType
    */
    abstract protected function loadComplexTypeBeforeCallbackCallback(
        Schema $schema,
        DOMElement $node
    );

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    abstract protected function loadComplexType(Schema $schema, DOMElement $node, $callback = null);

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    abstract protected function makeCallbackCallback(
        Type $type,
        DOMElement $node,
        Closure $callbackCallback,
        $callback = null
    );

    /**
    * @param Closure|null $callback
    */
    abstract protected function runCallbackAgainstDOMNodeList(
        Type $type,
        DOMElement $node,
        Closure $againstNodeList,
        $callback = null
    );

    abstract protected function loadComplexTypeFromChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode,
        Schema $schema
    );

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    abstract protected function loadSimpleType(Schema $schema, DOMElement $node, $callback = null);

    abstract protected function loadList(SimpleType $type, DOMElement $node);

    /**
    * @param string $attributeName
    *
    * @return SchemaItem
    */
    abstract protected function findSomeType(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    );

    /**
    * @param string $attributeName
    *
    * @return SchemaItem
    */
    abstract protected function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    );

    abstract protected function loadUnion(SimpleType $type, DOMElement $node);

    /**
    * @param bool $checkAbstract
    */
    abstract protected function fillTypeNode(Type $type, DOMElement $node, $checkAbstract = false);

    abstract protected function loadExtensionChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode
    );

    abstract protected function loadExtension(BaseComplexType $type, DOMElement $node);

    public function findAndSetSomeBase(
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

    abstract protected function maybeLoadExtensionFromBaseComplexType(
        Type $type,
        DOMElement $childNode
    );

    abstract protected function loadRestriction(Type $type, DOMElement $node);

    /**
    * @param string $typeName
    *
    * @return mixed[]
    */
    abstract protected static function splitParts(DOMElement $node, $typeName);

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
        list ($name, $namespace) = static::splitParts($node, $typeName);

        /**
        * @var string|null $namespace
        */
        $namespace = $namespace ?: $schema->getTargetNamespace();

        try {
            /**
            * @var ElementItem|Group|AttributeItem|AttributeGroup|Type $out
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
    abstract protected function loadElementDef(Schema $schema, DOMElement $node);

    public function fillItem(Item $element, DOMElement $node)
    {
        $skip = false;
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $element,
                & $skip
            ) {
                if (
                    ! $skip &&
                    in_array(
                        $childNode->localName,
                        [
                            'complexType',
                            'simpleType',
                        ]
                    )
                ) {
                    Type::loadTypeWithCallback(
                        $this,
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

    abstract protected function fillItemNonLocalType(Item $element, DOMElement $node);

    /**
    * @var Schema|null
    */
    protected $globalSchema;

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
    * @return string[]
    */
    public function getGlobalSchemaInfo()
    {
        return self::$globalSchemaInfo;
    }

    /**
     *
     * @return Schema
     */
    public function getGlobalSchema()
    {
        if (!$this->globalSchema) {
            $callbacks = array();
            $globalSchemas = $this->setupGlobalSchemas($callbacks);

            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], "anySimpleType"));
            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], "anyType"));

            $globalSchemas[static::XML_NS]->addSchema(
                $globalSchemas[static::XSD_NS],
                (string) static::XSD_NS
            );
            $globalSchemas[static::XSD_NS]->addSchema(
                $globalSchemas[static::XML_NS],
                (string) static::XML_NS
            );

            /**
            * @var Closure $callback
            */
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
        Schema::setLoadedFile($fileKey, $rootSchema = new Schema());

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
    public function getNamespaceSpecificFileIndex($file, $targetNamespace)
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
    public function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->load($file)) {
            throw new IOException("Can't load the file $file");
        }
        return $xml;
    }

    public static function againstDOMNodeList(
        DOMElement $node,
        Closure $againstNodeList
    ) {
        $limit = $node->childNodes->length;
        for ($i = 0; $i < $limit; $i += 1) {
            /**
            * @var DOMNode $childNode
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
}
