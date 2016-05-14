<?php
namespace GoetasWebservices\XML\XSDReader;

use DOMDocument;
use DOMElement;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeRef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;

class SchemaReader
{

    const XSD_NS = "http://www.w3.org/2001/XMLSchema";

    const XML_NS = "http://www.w3.org/XML/1998/namespace";

    private $loadedFiles = array();

    private $knowLocationSchemas = array();

    private static $globalSchemaInfo = array(
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd'
    );

    public function __construct()
    {
        $this->addKnownSchemaLocation('http://www.w3.org/2001/xml.xsd', __DIR__ . '/Resources/xml.xsd');
        $this->addKnownSchemaLocation('http://www.w3.org/2001/XMLSchema.xsd', __DIR__ . '/Resources/XMLSchema.xsd');
    }

    public function addKnownSchemaLocation($remote, $local)
    {
        $this->knowLocationSchemas[$remote] = $local;
    }

    private function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        $attGroup = new AttributeGroup($schema, $node->getAttribute("name"));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schema, $node, $attGroup) {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $attribute = $this->findSomething('findAttribute', $schema, $node, $childNode->getAttribute("ref"));
                        } else {
                            $attribute = $this->loadAttribute($schema, $childNode);
                        }
                        $attGroup->addAttribute($attribute);
                        break;
                    case 'attributeGroup':

                        $attribute = $this->findSomething('findAttributeGroup', $schema, $node, $childNode->getAttribute("ref"));
                        $attGroup->addAttribute($attribute);
                        break;
                }
            }
        };
    }

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


    private function loadAttributeDef(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeDef($schema, $node->getAttribute("name"));

        $schema->addAttribute($attribute);

        return function () use ($attribute, $schema, $node) {
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

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param Schema $parent
     * @return array
     */
    private function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $schema->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("targetNamespace")) {
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace());
        }
        $schema->setElementsQualification(!$node->hasAttribute("elementFormDefault") || $node->getAttribute("elementFormDefault") == "qualified");
        $schema->setAttributesQualification(!$node->hasAttribute("attributeFormDefault") || $node->getAttribute("attributeFormDefault") == "qualified");
        $schema->setDoc($this->getDocumentation($node));
        $functions = array();

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'include':
                case 'import':
                    $functions[] = $this->loadImport($schema, $childNode);
                    break;
                case 'element':
                    $functions[] = $this->loadElementDef($schema, $childNode);
                    break;
                case 'attribute':
                    $functions[] = $this->loadAttributeDef($schema, $childNode);
                    break;
                case 'attributeGroup':
                    $functions[] = $this->loadAttributeGroup($schema, $childNode);
                    break;
                case 'group':
                    $functions[] = $this->loadGroup($schema, $childNode);
                    break;
                case 'complexType':
                    $functions[] = $this->loadComplexType($schema, $childNode);
                    break;
                case 'simpleType':
                    $functions[] = $this->loadSimpleType($schema, $childNode);
                    break;
            }
        }

        return $functions;
    }

    private function loadElement(Schema $schema, DOMElement $node)
    {
        $element = new Element($schema, $node->getAttribute("name"));
        $element->setDoc($this->getDocumentation($node));

        $this->fillItem($element, $node);

        if ($node->hasAttribute("maxOccurs")) {
            $element->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }
        if ($node->hasAttribute("minOccurs")) {
            $element->setMin((int)$node->getAttribute("minOccurs"));
        }
        if ($node->hasAttribute("nillable")) {
            $element->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $element->setQualified($node->getAttribute("form") == "qualified");
        }
        return $element;
    }

    private function loadGroupRef(Group $referenced, DOMElement $node)
    {
        $ref = new GroupRef($referenced);
        $ref->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("maxOccurs")) {
            $ref->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }
        if ($node->hasAttribute("minOccurs")) {
            $ref->setMin((int)$node->getAttribute("minOccurs"));
        }

        return $ref;
    }

    private function loadElementRef(ElementDef $referenced, DOMElement $node)
    {
        $ref = new ElementRef($referenced);
        $ref->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("maxOccurs")) {
            $ref->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }
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


    private function loadAttributeRef(AttributeDef $referencedAttribiute, DOMElement $node)
    {
        $attribute = new AttributeRef($referencedAttribiute);
        $attribute->setDoc($this->getDocumentation($node));

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
                    $referencedGroup = $this->findSomething('findGroup', $elementContainer->getSchema(), $node, $childNode->getAttribute("ref"));

                    $group = $this->loadGroupRef($referencedGroup, $childNode);
                    $elementContainer->addElement($group);
                    break;
            }
        }
    }

    private function loadGroup(Schema $schema, DOMElement $node)
    {
        $group = new Group($schema, $node->getAttribute("name"));
        $group->setDoc($this->getDocumentation($node));

        if ($node->hasAttribute("maxOccurs")) {
            $group->setMax($node->getAttribute("maxOccurs") == "unbounded" ? -1 : (int)$node->getAttribute("maxOccurs"));
        }
        if ($node->hasAttribute("minOccurs")) {
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
                        $this->loadSequence($type, $childNode);
                        break;
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $referencedAttribute = $this->findSomething('findAttribute', $schema, $node, $childNode->getAttribute("ref"));
                            $attribute = $this->loadAttributeRef($referencedAttribute, $childNode);
                        } else {
                            $attribute = $this->loadAttribute($schema, $childNode);
                        }

                        $type->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        $attribute = $this->findSomething('findAttributeGroup', $schema, $node, $childNode->getAttribute("ref"));
                        $type->addAttribute($attribute);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

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
            $type->setList($this->findSomething('findType', $type->getSchema(), $node, $node->getAttribute("itemType")));
        } else {
            $addCallback = function ($list) use ($type) {
                $type->setList($list);
            };

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'simpleType':
                        call_user_func($this->loadSimpleType($type->getSchema(), $childNode, $addCallback));
                        break;
                }
            }
        }
    }

    private function loadUnion(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute("memberTypes")) {
            $types = preg_split('/\s+/', $node->getAttribute("memberTypes"));
            foreach ($types as $typeName) {
                $type->addUnion($this->findSomething('findType', $type->getSchema(), $node, $typeName));
            }
        }
        $addCallback = function ($unType) use ($type) {
            $type->addUnion($unType);
        };

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'simpleType':
                    call_user_func($this->loadSimpleType($type->getSchema(), $childNode, $addCallback));
                    break;
            }
        }
    }

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
                    $this->loadExtension($type, $childNode);
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
            $parent = $this->findSomething('findType', $type->getSchema(), $node, $node->getAttribute("base"));
            $extension->setBase($parent);
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'sequence':
                case 'choice':
                case 'all':
                    $this->loadSequence($type, $childNode);
                    break;
                case 'attribute':
                    if ($childNode->hasAttribute("ref")) {
                        $attribute = $this->findSomething('findAttribute', $type->getSchema(), $node, $childNode->getAttribute("ref"));
                    } else {
                        $attribute = $this->loadAttribute($type->getSchema(), $childNode);
                    }
                    $type->addAttribute($attribute);
                    break;
                case 'attributeGroup':
                    $attribute = $this->findSomething('findAttributeGroup', $type->getSchema(), $node, $childNode->getAttribute("ref"));
                    $type->addAttribute($attribute);
                    break;
            }
        }
    }

    private function loadRestriction(Type $type, DOMElement $node)
    {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute("base")) {
            $restrictedType = $this->findSomething('findType', $type->getSchema(), $node, $node->getAttribute("base"));
            $restriction->setBase($restrictedType);
        } else {
            $addCallback = function ($restType) use ($restriction) {
                $restriction->setBase($restType);
            };

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'simpleType':
                        call_user_func($this->loadSimpleType($type->getSchema(), $childNode, $addCallback));
                        break;
                }
            }
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
                    'maxExclusive'
                ], true)) {
                $restriction->addCheck($childNode->localName,
                    [
                        'value' => $childNode->getAttribute("value"),
                        'doc' => $this->getDocumentation($childNode)
                    ]);
            }
        }
    }

    private static function splitParts(DOMElement $node, $typeName)
    {
        $namespace = null;
        $prefix = null;
        $name = $typeName;
        if (strpos($typeName, ':') !== false) {
            list ($prefix, $name) = explode(':', $typeName);
        }

        $namespace = $node->lookupNamespaceURI($prefix ?: null);
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
     * @return ElementItem|Group|AttributeItem|AttribiuteGroup|Type
     */
    private function findSomething($finder, Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);

        $namespace = $namespace ?: $schema->getTargetNamespace();

        try {
            return $schema->$finder($name, $namespace);
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", strtolower(substr($finder, 4)), $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

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
            $addCallback = function ($type) use ($element) {
                $element->setType($type);
            };
            switch ($localType->localName) {
                case 'complexType':
                    call_user_func($this->loadComplexType($element->getSchema(), $localType, $addCallback));
                    break;
                case 'simpleType':
                    call_user_func($this->loadSimpleType($element->getSchema(), $localType, $addCallback));
                    break;
            }
        } else {

            if ($node->getAttribute("type")) {
                $type = $this->findSomething('findType', $element->getSchema(), $node, $node->getAttribute("type"));
            } else {
                $type = $this->findSomething('findType', $element->getSchema(), $node, ($node->lookupPrefix(self::XSD_NS) . ":anyType"));
            }

            $element->setType($type);
        }
    }

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

        $xml = $this->getDOM(isset($this->knowLocationSchemas[$file]) ? $this->knowLocationSchemas[$file] : $file);

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

    private $globalSchema;

    /**
     *
     * @return \GoetasWebservices\XML\XSDReader\Schema\Schema
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
                $xml = $this->getDOM($this->knowLocationSchemas[$uri]);
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
        return $this->globalSchema;
    }

    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Schema
     */
    public function readNode(\DOMNode $node, $file = 'schema.xsd')
    {
        $this->loadedFiles[$file] = $rootSchema = new Schema();

        $rootSchema->addSchema($this->getGlobalSchema());
        $callbacks = $this->schemaNode($rootSchema, $node);

        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }


    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Schema
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
     * @return \GoetasWebservices\XML\XSDReader\Schema\Schema
     */
    public function readFile($file)
    {
        $xml = $this->getDOM($file);
        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     * @throws IOException
     * @return \DOMDocument
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
