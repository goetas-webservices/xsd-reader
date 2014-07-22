<?php
namespace Goetas\XML\XSDReader;

use DOMDocument;
use DOMElement;
use Exception;
use Goetas\XML\XSDReader\Utils\UrlUtils;
use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Element\ElementReal;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeReal;
use Goetas\XML\XSDReader\Schema\Type\ComplexType;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Type\BaseComplexType;
use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;
use Goetas\XML\XSDReader\Exception\TypeException;
use Goetas\XML\XSDReader\Exception\IOException;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeGroup;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Schema\Element\ElementHolder;
use Goetas\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use Goetas\XML\XSDReader\Schema\Element\ElementNode;
use Goetas\XML\XSDReader\Schema\Inheritance\Restriction;
use Goetas\XML\XSDReader\Schema\Inheritance\Extension;

class SchemaReader
{

    const XSD_NS = "http://www.w3.org/2001/XMLSchema";

    const XML_NS = "http://www.w3.org/XML/1998/namespace";

    public function __construct()
    {
    }

    protected function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        $attGroup = new AttributeGroup($schema, $node->getAttribute("name"));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use($schema, $node, $attGroup)
        {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $attribute = $this->findAttribute($schema, $node, $childNode->getAttribute("ref"));
                        } else {
                            $attribute = $this->loadAttributeReal($schema, $childNode);
                        }
                        $attGroup->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        $attribute = $this->findAttributeGroup($schema, $node, $childNode->getAttribute("ref"));
                        $attGroup->addAttribute($attribute);
                        break;
                }
            }
        };
    }

    protected function loadAttribute(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeReal($schema, $node->getAttribute("name"));

        $schema->addAttribute($attribute);

        return function () use($attribute, $schema, $node)
        {
            $this->fillTypeNodeChild($attribute, $node);
        };
    }

    protected function getDocumentation(DOMElement $node)
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
        return $doc;
    }

    protected function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $schema->setDoc($this->getDocumentation($node));
        if ($node->hasAttribute("targetNamespace")) {
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $schema->setTargetNamespace($parent->getTargetNamespace("targetNamespace"));
        }
        $schema->setElementsQualification(! $node->hasAttribute("elementFormDefault") || $node->getAttribute("elementFormDefault") == "qualified");
        $schema->setAttributesQualification(! $node->hasAttribute("attributeFormDefault") || $node->getAttribute("attributeFormDefault") == "qualified");
        $schema->setDoc($this->getDocumentation($node));
        $functions = array();

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'include':
                case 'import':
                    $functions[] = $this->loadImport($schema, $childNode);
                    break;
                case 'element':
                    $functions[] = $this->loadElement($schema, $childNode);
                    break;
                case 'attribute':
                    $functions[] = $this->loadAttribute($schema, $childNode);
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

        return array_filter($functions);
    }

    protected function loadElementReal(Schema $schema, DOMElement $node)
    {
        $element = new ElementReal($schema, $node->getAttribute("name"));
        $element->setDoc($this->getDocumentation($node));

        $this->fillTypeNodeChild($element, $node);

        if ($node->hasAttribute("maxOccurs")) {
            $element->setMax($node->getAttribute("maxOccurs") == "unbounded" ? - 1 : $node->getAttribute("maxOccurs"));
        }
        if ($node->hasAttribute("minOccurs")) {
            $element->setMin($node->getAttribute("minOccurs"));
        }
        if ($node->hasAttribute("nillable")) {
            $element->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $element->setQualified($node->getAttribute("form") == "qualified");
        }
        return $element;
    }

    protected function loadAttributeReal(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeReal($schema, $node->getAttribute("name"));
        $attribute->setDoc($this->getDocumentation($node));
        $this->fillTypeNodeChild($attribute, $node);

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

    protected function loadSequence(ElementHolder $elementHolder, DOMElement $node)
    {
        foreach ($node->childNodes as $childNode) {

            switch ($childNode->localName) {
                case 'element':
                    if ($childNode->hasAttribute("ref")) {
                        $element = $this->findElement($elementHolder->getSchema(), $node, $childNode->getAttribute("ref"));
                    } else {
                        $element = $this->loadElementReal($elementHolder->getSchema(), $childNode);
                    }
                    $elementHolder->addElement($element);
                    break;
                case 'group':
                    $element = $this->findGroup($elementHolder->getSchema(), $node, $childNode->getAttribute("ref"));
                    $elementHolder->addElement($element);
                    break;
            }
        }
    }

    protected function loadGroup(Schema $schema, DOMElement $node)
    {
        $type = new Group($schema, $node->getAttribute("name"));
        $type->setDoc($this->getDocumentation($node));
        $schema->addGroup($type);

        return function () use($type, $node, $schema)
        {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choiche':
                        $this->loadSequence($type, $childNode);
                        break;
                }
            }
        };
    }

    protected function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        $isSimple = false;

        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName === "simpleContent") {
                $isSimple = true;
            }
        }

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute("name")) : new ComplexType($schema, $node->getAttribute("name"));

        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        return function () use($type, $node, $schema, $callback)
        {

            $this->fillTypeNode($type, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choiche':
                        $this->loadSequence($type, $childNode);
                        break;
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $attribute = $this->findAttribute($schema, $node, $childNode->getAttribute("ref"));
                        } else {
                            $attribute = $this->loadAttributeReal($schema, $childNode);
                        }

                        $type->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        $attribute = $this->findAttributeGroup($schema, $node, $childNode->getAttribute("ref"));
                        $type->addAttribute($attribute);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    protected function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType($schema, $node->getAttribute("name"));
        $type->setDoc($this->getDocumentation($node));
        if ($node->getAttribute("name")) {
            $schema->addType($type);
        }

        return function () use($type, $schema, $node, $callback)
        {
            $this->fillTypeNode($type, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'union':
                        $this->loadUnion($type, $childNode);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    protected function loadUnion(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute("memberTypes")) {
            $types = preg_split('/\s+/', $node->getAttribute("memberTypes"));
            foreach ($types as $typeName) {
                $type->addUnion($this->findType($type->getSchema(), $node, $typeName));
            }
        }
        $addCallback = function ($unType) use($type)
        {
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

    protected function fillTypeNode(Type $type, DOMElement $node)
    {
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
                    $this->fillTypeNode($type, $childNode);
                    break;
            }
        }
    }

    protected function loadExtension(BaseComplexType $type, DOMElement $node)
    {
        $extension = new Extension();
        $type->setExtension($extension);

        if ($node->hasAttribute("base")) {
            $parent = $this->findType($type->getSchema(), $node, $node->getAttribute("base"));
            $extension->setBase($parent);
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'attribute':
                    if ($childNode->hasAttribute("ref")) {
                        $attribute = $this->findAttribute($type->getSchema(), $node, $childNode->getAttribute("ref"));
                    } else {
                        $attribute = $this->loadAttributeReal($type->getSchema(), $childNode);
                    }
                    $type->addAttribute($attribute);
                    break;
                case 'attributeGroup':
                    $attribute = $this->findAttributeGroup($type->getSchema(), $node, $childNode->getAttribute("ref"));
                    $type->addAttribute($attribute);
                    break;
            }
        }
    }

    protected function loadRestriction(Type $type, DOMElement $node)
    {
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute("base")) {
            $restrictedType = $this->findType($type->getSchema(), $node, $node->getAttribute("base"));
            $restriction->setBase($restrictedType);
        } else {
            $addCallback = function ($restType) use($restriction)
            {
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
            if (in_array($childNode->localName, [
                'enumeration',
                'pattern',
                'length',
                'minLength',
                'maxLength',
                'minInclusve',
                'maxInclusve',
                'minExclusve',
                'maxEXclusve'
            ], true)) {
                $restriction->addCheck($childNode->localName, [
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
            $namespace = $node->lookupNamespaceURI($prefix);
        }
        return array(
            $name,
            $namespace,
            $prefix
        );
    }

    protected function findType(Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);

        if ($name == "OTA_CodeType") {
            try {
                return $schema->findType($name, $namespace ?  : $schema->getTargetNamespace());
            } catch (Exception $e) {
                var_Dump($schema->getFile());
                var_Dump($schema->getTargetNamespace());
                var_Dump($node->ownerDocument->saveXML($node));
                die();
            }
        }

        try {
            return $schema->findType($name, $namespace ?  : $schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il tipo $typeName $namespace, alla riga " . $node->getLineNo() . " di " . $node->ownerDocument->documentURI, 0, $e);
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param string $typeName
     * @throws TypeException
     * @return Group
     */
    protected function findGroup(Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);
        try {
            return $schema->findGroup($name, $namespace ?  : $schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il gruppo, alla riga " . $node->getLineNo() . " di " . $node->ownerDocument->documentURI, 0, $e);
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param string $typeName
     * @throws TypeException
     * @return Group
     */
    protected function findElement(Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);
        try {
            return $schema->findElement($name, $namespace ?  : $schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo l'elemento, alla riga " . $node->getLineNo() . " di " . $node->ownerDocument->documentURI, 0, $e);
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param string $typeName
     * @throws TypeException
     * @return Attribute
     */
    protected function findAttribute(Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);
        try {
            return $schema->findAttribute($name, $namespace ?  : $schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo l'attributo, alla riga " . $node->getLineNo() . " di " . $node->ownerDocument->documentURI, 0, $e);
        }
    }

    /**
     *
     * @param Schema $schema
     * @param DOMElement $node
     * @param string $typeName
     * @throws TypeException
     * @return Attribute
     */
    protected function findAttributeGroup(Schema $schema, DOMElement $node, $typeName)
    {
        list ($name, $namespace) = self::splitParts($node, $typeName);
        try {
            return $schema->findAttributeGroup($name, $namespace ?  : $schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il gruppo di attributi, alla riga " . $node->getLineNo() . " di " . $node->ownerDocument->documentURI, 0, $e);
        }
    }

    protected function loadElement(Schema $schema, DOMElement $node)
    {
        $element = new ElementNode($schema, $node->getAttribute("name"));
        $schema->addElement($element);

        return function () use($element, $node)
        {
            $this->fillTypeNodeChild($element, $node);
        };
    }

    protected function fillTypeNodeChild(TypeNodeChild $element, DOMElement $node)
    {
        $element->setIsAnonymousType(! $node->hasAttribute("type"));

        if ($element->isAnonymousType()) {

            $addCallback = function ($type) use($element)
            {
                $element->setType($type);
            };
            $functions = array();
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'complexType':
                        $functions[] = $this->loadComplexType($element->getSchema(), $childNode, $addCallback);
                        break;
                    case 'simpleType':
                        $functions[] = $this->loadSimpleType($element->getSchema(), $childNode, $addCallback);
                        break;
                }
            }
            array_map('call_user_func', array_filter($functions));
        } else {
            $type = $this->findType($element->getSchema(), $node, $node->getAttribute("type"));
            $element->setType($type);
        }
    }

    protected function getSchemaCid($file, \DOMNode $xml)
    {
        return $file . "|" . $xml->documentElement->getAttribute("targetNamespace");
    }

    protected function loadImport(Schema $schema, DOMElement $node)
    {
        if (! $node->getAttribute("schemaLocation")) {
            return function ()
            {
            };
        }
        if (in_array($node->getAttribute("schemaLocation"), array(
            'http://www.w3.org/2001/xml.xsd'
        ))) {
            return function ()
            {
            };
        }

        $file = UrlUtils::resolve_url($node->ownerDocument->documentURI, $node->getAttribute("schemaLocation"));

        $xml = null;
        $targetNS = null;
        if ($node->hasAttribute("namespace")) {
            $cid = $file . "|" . $node->getAttribute("namespace");
        } else {

            $xml = $this->getDOM($file);

            if ($xml->documentElement->hasAttribute("targetNamespace")) {
                $cid = $this->getSchemaCid($file, $xml);
                $targetNS = $xml->documentElement->getAttribute("targetNamespace");
            } else {
                $cid = $file . "|" . $schema->getTargetNamespace();
                $targetNS = $schema->getTargetNamespace();
            }
        }

        $ns = $node->hasAttribute("namespace") ? $node->getAttribute("namespace") : null;

        if (isset($this->loadedFiles[$cid])) {
            $schema->addSchema($this->loadedFiles[$cid], $ns);
            return function ()
            {
            };
        }

        $this->loadedFiles[$cid] = $newSchema = new Schema();

        $newSchema->setTargetNamespace($targetNS);

        $newSchema->setFile($file);
        foreach ($this->globalSchemas as $globaSchemaNS => $globaSchema) {
            $newSchema->addSchema($globaSchema, $globaSchemaNS);
        }
        $schema->addSchema($newSchema, $ns);

        $callbacks = $this->schemaNode($newSchema, $xml->documentElement, $schema);

        return function () use($callbacks)
        {
            array_map('call_user_func', $callbacks);
        };
    }

    private $globalSchemas = array();

    protected function addGlobalSchemas(Schema $rootSchema)
    {
        $callbacksAll = array();
        if (! $this->globalSchemas) {
            $preload = array(
                self::XSD_NS => __DIR__ . '/Resources/XMLSchema.xsd',
                self::XML_NS => __DIR__ . '/Resources/xml.xsd'
            );
            foreach ($preload as $key => $filePath) {
                $this->globalSchemas[$key] = $schema = new Schema();
                $schema->setFile($filePath);

                $xml = $this->getDOM($filePath);

                $callbacks = $this->schemaNode($schema, $xml->documentElement);

                $callbacksAll[] = function () use($callbacks)
                {
                    array_map('call_user_func', $callbacks);
                };
            }

            $this->globalSchemas[self::XSD_NS]->addType(new SimpleType($this->globalSchemas[self::XSD_NS], "anySimpleType"));

            $this->globalSchemas[self::XML_NS]->addSchema($this->globalSchemas[self::XSD_NS], self::XSD_NS);
            $this->globalSchemas[self::XSD_NS]->addSchema($this->globalSchemas[self::XML_NS], self::XML_NS);
        }
        foreach ($this->globalSchemas as $globalSchema) {
            $rootSchema->addSchema($globalSchema);
        }
        return $callbacksAll;
    }

    private $loadedFiles = array();

    public function readFile($file)
    {
        $xml = $this->getDOM($file);

        $cid = $this->getSchemaCid($file, $xml);

        $this->loadedFiles[$cid] = $rootSchema = new Schema();
        $rootSchema->setFile($file);

        $callbacksAll = array();

        $callbacks = $this->addGlobalSchemas($rootSchema);

        $callbacksAll[] = function () use($callbacks)
        {
            array_map('call_user_func', $callbacks);
        };

        $callbacks = $this->schemaNode($rootSchema, $xml->documentElement);
        $callbacksAll[] = function () use($callbacks)
        {
            array_map('call_user_func', $callbacks);
        };

        array_map('call_user_func', $callbacksAll);

        return $rootSchema;
    }

    /**
     *
     * @param string $content
     * @param string $fileName
     * @return \Goetas\XML\XSDReader\Schema\Schema
     */
    public function readString($content, $file = 'schema.xsd')
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->loadXML($content)) {
            throw new IOException("Non riesco a caricare lo schema");
        }
        $xml->documentURI = $file;
        $cid = $this->getSchemaCid($file, $xml);

        $this->loadedFiles[$cid] = $rootSchema = new Schema();
        $rootSchema->setFile($file);

        $callbacksAll = array();

        $callbacks = $this->addGlobalSchemas($rootSchema);

        $callbacksAll[] = function () use($callbacks)
        {
            array_map('call_user_func', $callbacks);
        };

        $callbacks = $this->schemaNode($rootSchema, $xml->documentElement);

        $callbacksAll[] = function () use($callbacks)
        {
            array_map('call_user_func', $callbacks);
        };

        array_map('call_user_func', $callbacksAll);

        return $rootSchema;
    }

    private function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->load($file)) {
            throw new IOException("Non riesco a caricare lo schema");
        }
        return $xml;
    }
}
