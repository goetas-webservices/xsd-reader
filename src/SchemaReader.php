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
use Doctrine\Common\Annotations\Annotation\Attribute;
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
    public function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        $attGroup = new AttributeGroup();
        $attGroup->setName($node->getAttribute("name"));
        $attGroup->setDoc($this->getDocumentation($node));
        $schema->addAttributeGroup($attGroup);
        $_this = $this;
        return function () use($schema, $node, $attGroup, $_this)
        {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $attribute = $_this->findAttribute($schema, $node, $childNode->getAttribute("ref"));
                        }else{
                            $attribute = $_this->loadAttributeReal($schema, $childNode);
                        }
                        $attGroup->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        $attribute = $_this->findAttributeGroup($schema, $node, $childNode->getAttribute("ref"));
                        $attGroup->addAttribute($attribute);
                        break;
                }
            }
        };
    }

    public function loadAttribute(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeReal();
        $attribute->setName($node->getAttribute("name"));


        $schema->addAttribute($attribute);

        $_this = $this;

        return function () use($attribute, $schema, $node, $_this)
        {
            $_this->fillTypeNodeChild($attribute, $schema, $node);
        };
    }

    protected function getDocumentation(DOMElement $node)
    {
        $doc = '';
        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName=="annotation") {
                foreach ($childNode->childNodes as $subChildNode) {
                    if ($subChildNode->localName=="documentation") {
                        $doc .= ($subChildNode->nodeValue);
                    }
                }
            }
        }
        return $doc;
    }
    public function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {

        $schema->setDoc($this->getDocumentation($node));
        if($node->hasAttribute("targetNamespace")){
            $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        }elseif($parent){
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
    public function loadElementReal(Schema $schema, DOMElement $node)
    {
        $element = new ElementReal();
        $element->setDoc($this->getDocumentation($node));
        $element->setName($node->getAttribute("name"));

        $this->fillTypeNodeChild($element, $schema, $node);

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
            $element->setQualified($node->getAttribute("form") == "qualidied");
        }
        return $element;
    }


    public function loadAttributeReal(Schema $schema, DOMElement $node)
    {
        $attribute = new AttributeReal();
        $attribute->setDoc($this->getDocumentation($node));
        $attribute->setName($node->getAttribute("name"));
        $this->fillTypeNodeChild($attribute, $schema, $node);

        if ($node->hasAttribute("nillable")) {
            $attribute->setNil($node->getAttribute("nillable") == "true");
        }
        if ($node->hasAttribute("form")) {
            $attribute->setQualified($node->getAttribute("form") == "qualidied");
        }
        if ($node->hasAttribute("use")) {
            $attribute->setUse($node->getAttribute("use"));
        }
        return $attribute;
    }

    public function loadSequence(ElementHolder $elementHolder, Schema $schema, DOMElement $node)
    {
        foreach ($node->childNodes as $childNode) {

            switch ($childNode->localName) {
                case 'element':
                    if ($childNode->hasAttribute("ref")) {
                        $element = $this->findElement($schema, $node, $childNode->getAttribute("ref"));
                    } else {
                        $element = $this->loadElementReal($schema, $childNode);
                    }
                    $elementHolder->addElement($element);
                    break;
                case 'group':
                    $element = $this->findGroup($schema, $node, $childNode->getAttribute("ref"));
                    $elementHolder->addElement($element);
                    break;
            }
        }
    }

    public function loadGroup(Schema $schema, DOMElement $node)
    {
        $type = new Group();
        $type->setDoc($this->getDocumentation($node));
        $type->setName($node->getAttribute("name"));
        $schema->addGroup($type);

        $_this = $this;
        return function () use($type, $node, $schema, $_this)
        {
            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choiche':
                        $_this->loadSequence($type, $schema, $childNode);
                        break;
                }
            }
        };
    }

    public function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        $isSimple = false;

        foreach ($node->childNodes as $childNode) {
            if ($childNode->localName === "simpleContent") {
                $isSimple = true;
            }
        }

        $type = $isSimple ? new ComplexTypeSimpleContent() : new ComplexType();

        $type->setName($node->getAttribute("name"));
        $type->setDoc($this->getDocumentation($node));
        if($node->getAttribute("name")){
            $schema->addType($type);
        }

        $_this = $this;
        return function () use($type, $node, $schema, $_this, $callback)
        {

            $_this->fillTypeNode($type, $schema, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'sequence':
                    case 'choiche':
                        $_this->loadSequence($type, $schema, $childNode);
                        break;
                    case 'attribute':
                        if ($childNode->hasAttribute("ref")) {
                            $attribute = $_this->findAttribute($schema, $node, $childNode->getAttribute("ref"));
                        } else {
                            $attribute = $_this->loadAttributeReal($schema, $childNode);
                        }

                        $type->addAttribute($attribute);
                        break;
                    case 'attributeGroup':
                        $attribute = $_this->findAttributeGroup($schema, $node, $childNode->getAttribute("ref"));
                        $type->addAttribute($attribute);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    public function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType();
        $type->setDoc($this->getDocumentation($node));
        $type->setName($node->getAttribute("name"));
        if($node->getAttribute("name")){
            $schema->addType($type);
        }
        $_this = $this;
        return function () use ($type, $schema, $node, $_this, $callback)
        {
            $_this->fillTypeNode($type, $schema, $node);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
                    case 'union':
                        $_this->loadUnion($type, $schema, $childNode);
                        break;
                }
            }

            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }
    public function loadUnion(SimpleType $type, Schema $schema, DOMElement $node)
    {
        if ($node->hasAttribute("memberTypes")) {
            $types = preg_split('/\s+/', $node->getAttribute("memberTypes"));
            foreach ($types as $typeName){
                $type->addUnion($this->findType($schema, $node, $typeName));
            }
        }
        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'simpleType':
                    call_user_func($this->loadSimpleType($schema, $childNode));
                    break;
            }
        }
    }

    public function fillTypeNode(Type $type, Schema $schema, DOMElement $node)
    {
        $type->setSchema($schema);
        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'restriction':
                    $this->loadRestriction($type, $schema, $childNode);
                    break;
                case 'extension':
                    $this->loadExtension($type, $schema, $childNode);
                    break;
                case 'simpleContent':
                case 'complexContent':
                    $this->fillTypeNode($type, $schema, $childNode);
                    break;
            }
        }
    }

    public function loadExtension(BaseComplexType $type, Schema $schema, DOMElement $node)
    {

        $extension = new Extension();
        $type->setExtends($extension);

        if($node->hasAttribute("base")){
            $parent = $this->findType($schema, $node, $node->getAttribute("base"));
            $extension->setBase($parent);
        }

        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
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


    }

    public function loadRestriction(Type $type, Schema $schema, DOMElement $node)
    {
        $restriction = new Restriction();
        if($node->hasAttribute("base")){
            $restrictedType = $this->findType($schema, $node, $node->getAttribute("base"));
            $restriction->setBase($restrictedType);
        }
        $type->setRestriction($restriction);

        foreach ($node->childNodes as $childNode) {
            if (in_array($childNode->localName, ['enumeration', 'pattern', 'length', 'minLength', 'maxLength', 'minInclusve', 'maxInclusve', 'minExclusve', 'maxEXclusve'], true)){
                $restriction->addCheck($childNode->localName, ['value'=>$childNode->getAttribute("value"), 'doc'=>$this->getDocumentation($childNode)]);
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

        if($name=="OTA_CodeType"){
            try {
                return $schema->findType($name, $namespace?:$schema->getTargetNamespace());
            } catch (Exception $e) {
                var_Dump($schema->getFile());
                var_Dump($schema->getTargetNamespace());
                var_Dump($node->ownerDocument->saveXML($node));
                die();
            }

        }

        try {
            return $schema->findType($name, $namespace?:$schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il tipo $typeName $namespace, alla riga ".$node->getLineNo()." di ".$node->ownerDocument->documentURI, 0, $e);
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
            return $schema->findGroup($name, $namespace?:$schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il gruppo, alla riga ".$node->getLineNo()." di ".$node->ownerDocument->documentURI, 0, $e);
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
            return $schema->findElement($name, $namespace?:$schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo l'elemento, alla riga ".$node->getLineNo()." di ".$node->ownerDocument->documentURI, 0, $e);
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
            return $schema->findAttribute($name, $namespace?:$schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo l'attributo, alla riga ".$node->getLineNo()." di ".$node->ownerDocument->documentURI, 0, $e);
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
            return $schema->findAttributeGroup($name, $namespace?:$schema->getTargetNamespace());
        } catch (Exception $e) {
            throw new TypeException("Non trovo il gruppo di attributi, alla riga ".$node->getLineNo()." di ".$node->ownerDocument->documentURI, 0, $e);
        }

    }

    public function loadElement(Schema $schema, DOMElement $node)
    {
        $element = new ElementNode();
        $schema->addElement($element);
        $element->setName($node->getAttribute("name"));

        $_this = $this;
        return function () use($element, $schema, $node, $_this)
        {
            $_this->fillTypeNodeChild($element, $schema, $node);
        };
    }

    public function fillTypeNodeChild(TypeNodeChild $element, Schema $schema, DOMElement $node)
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
                        $functions[] = $this->loadComplexType($schema, $childNode, $addCallback);
                        break;
                    case 'simpleType':
                        $functions[] = $this->loadSimpleType($schema, $childNode, $addCallback);
                        break;
                }
            }
            array_map('call_user_func', array_filter($functions));

        } else {
            $type = $this->findType($schema, $node, $node->getAttribute("type"));
            $element->setType($type);
        }
    }

    public function loadImport(Schema $schema, DOMElement $node)
    {

        if (! $node->getAttribute("schemaLocation")) {
            return function(){

            };
        }
        if (in_array($node->getAttribute("schemaLocation"), array(
            'http://www.w3.org/2001/xml.xsd'
        ))) {
            return function(){

            };
        }

        $file = UrlUtils::resolve_url($node->ownerDocument->documentURI, $node->getAttribute("schemaLocation"));

        if ($node->hasAttribute("namespace")) {
            $cid = $file."|".$node->hasAttribute("namespace");
        }else{

            $xml = $this->getDOM($file);

            if ($xml->documentElement->hasAttribute("targetNamespace")) {
                $cid = $file."|".$xml->documentElement->getAttribute("targetNamespace");
            }else{
                $cid = $file."|".$schema->getTargetNamespace();
            }
        }

        $ns = $node->hasAttribute("namespace")?$node->getAttribute("namespace"):null;

        if (isset($this->loadedFiles[$cid])) {
            $schema->addSchema($this->loadedFiles[$cid], $ns);
            return function(){

            };
        }

        $this->loadedFiles[$cid] = $newSchema = new Schema();
        $newSchema->setFile($file);
        foreach($this->globalSchemas as $globaSchemaNS => $globaSchema){
            $newSchema->addSchema($globaSchema, $globaSchemaNS);
        }
        $schema->addSchema($newSchema, $ns);

        $callbacks = $this->schemaNode($newSchema, $xml->documentElement, $schema);

        return function () use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };

    }
    private $globalSchemas = array();
    public function addGlobalSchemas(Schema $rootSchema)
    {

        $callbacksAll = array();
        if(!$this->globalSchemas){
            $preload = array(
                self::XSD_NS => __DIR__ . '/res/XMLSchema.xsd',
                self::XML_NS => __DIR__ . '/res/xml.xsd'
            );
            foreach($preload as $key => $filePath){
                $this->globalSchemas[$key] = $schema = new Schema();
                $schema->setFile($filePath);

                $callbacks = $this->loadSchemaFromFile($schema, $filePath);

                $callbacksAll[] = function() use ($callbacks) {
                    array_map('call_user_func', $callbacks);
                };
            }

            $this->globalSchemas[self::XSD_NS]->addType(new SimpleType("anySimpleType"));

            $this->globalSchemas[self::XML_NS]->addSchema($this->globalSchemas[self::XSD_NS], self::XSD_NS);
            $this->globalSchemas[self::XSD_NS]->addSchema($this->globalSchemas[self::XML_NS], self::XML_NS);
        }
        foreach($this->globalSchemas as $globalSchema){
            $rootSchema->addSchema($globalSchema);
        }
        return $callbacksAll;
    }
    private $loadedFiles = array();

    public function readFile($file)
    {

        $this->loadedFiles[$file] = $rootSchema = new Schema();
        $rootSchema->setFile($file);


        $callbacksAll = array();

        $callbacks = $this->addGlobalSchemas($rootSchema);

        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };

        $callbacks = $this->loadSchemaFromFile($rootSchema, $file);
        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };


        array_map('call_user_func', $callbacksAll);

        return $rootSchema;
    }
    public function readString($content, $fileName)
    {

        $this->loadedFiles[$fileName] = $rootSchema = new Schema();
        $rootSchema->setFile($fileName);


        $callbacksAll = array();

        $callbacks = $this->addGlobalSchemas($rootSchema);

        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };

        $callbacks = $this->loadSchemaFromString($rootSchema, $content, $fileName);
        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };


        array_map('call_user_func', $callbacksAll);

        return $rootSchema;
    }
    protected function loadSchemaFromFile(Schema $schema, $file, Schema $parent = null)
    {

        $xml = $this->getDOM($file);
        return $this->schemaNode($schema, $xml->documentElement, $parent);
    }
    protected function loadSchemaFromString(Schema $schema, $contents, $fileName, Schema $parent = null)
    {

        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->loadXML($contents)) {
            throw new IOException("Non riesco a caricare lo schema");
        }

        return $this->schemaNode($schema, $xml->documentElement, $parent);
    }
    private function getDOM($file){
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->load($file)) {
            throw new IOException("Non riesco a caricare lo schema");
        }
        return $xml;

    }

}
