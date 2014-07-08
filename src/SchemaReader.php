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

class SchemaReader
{

    public function __construct()
    {

    }

    private function loadDOM($file){
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (! $xml->load($file)) {
            throw new IOException("Non riesco a caricare lo schema {$file}");
        }
        return $xml;
    }
    public function loadAttributeGroup(Schema $schema, DOMElement $node)
    {
        $attGroup = new AttributeGroup();
        $attGroup->setName($node->getAttribute("name"));
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

    public function schemaNode(Schema $schema, DOMElement $node)
    {

        // echo "TNS: " . $node->getAttribute("targetNamespace")."\n";
        $schema->setTargetNamespace($node->getAttribute("targetNamespace"));
        $schema->setElementsQualification(! $node->hasAttribute("elementFormDefault") || $node->getAttribute("elementFormDefault") == "qualified");
        $schema->setAttributesQualification(! $node->hasAttribute("attributeFormDefault") || $node->getAttribute("attributeFormDefault") == "qualified");
        // $schema->setDoc($node->get(self::NS, "annotation"));
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
                        $element = $this->loadElementReal($schema, $childNode, $childNode->getAttribute("name"));
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
        $schema->addType($type);


        if($node->getAttribute("name")=="VehicleLocationLiabilitiesType"){
            echo "X";
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
        $type->setName($node->getAttribute("name"));
        $schema->addType($type);
        $_this = $this;
        return function () use($type, $schema, $node, $_this, $callback)
        {
            $_this->fillTypeNode($type, $schema, $node);
            if ($callback) {
                call_user_func($callback, $type);
            }
        };
    }

    public function fillTypeNode(Type $type, Schema $schema, DOMElement $node)
    {
        $functions = array();
        foreach ($node->childNodes as $childNode) {
            switch ($childNode->localName) {
                case 'restriction':
                    $functions[] = $this->loadRestriction($type, $schema, $childNode);
                    break;
                case 'extension':
                    $functions[] = $this->loadExtension($type, $schema, $childNode);
                    break;
            }
        }
        return function () use($functions)
        {
            array_map('call_user_func', array_filter($functions));
        };
    }

    public function loadExtension(BaseComplexType $type, Schema $schema, DOMElement $node)
    {
        $_this = $this;
        return function () use($type, $schema, $node, $_this)
        {
            $parent = $_this->findType($schema, $node, $node->getAttribute("base"));
            $type->setParent($parent);

            foreach ($node->childNodes as $childNode) {
                switch ($childNode->localName) {
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
        };
    }

    public function loadRestriction(Type $type, Schema $schema, DOMElement $node)
    {
        $_this = $this;
        return function () use($type, $schema, $node, $_this)
        {
            $parent = $_this->findType($schema, $node, $node->getAttribute("base"));
            $type->setParent($parent);
        };
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
        //var_dump($typeName." ".$node->getAttribute("name"));

        list ($name, $namespace) = self::splitParts($node, $typeName);
        try {
            return $schema->findType($name, $namespace);
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
            return $schema->findGroup($name, $namespace);
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
            return $schema->findElement($name, $namespace);
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
            return $schema->findAttribute($name, $namespace);
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
            return $schema->findAttributeGroup($name, $namespace);
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
            $_this->fillTypeNodeChild($element, $schema, $node, $element->getName());
        };
    }

    public function fillTypeNodeChild(TypeNodeChild $element, Schema $schema, DOMElement $node)
    {
        $element->setIsAnonymousType(! $node->hasAttribute("type"));

        if ($element->isAnonymousType()) {

            foreach ($node->childNodes as $childNode) {
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
            }
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

        if (isset($this->loadedFiles[$file])) {
            $schema->addSchema($this->loadedFiles[$file]);
            return function(){

            };
        }

        $this->loadedFiles[$file] = $newSchema = new Schema();
        $newSchema->setFile($file);
        $newSchema->addSchema($this->loadedFiles["xml"]);
        $newSchema->addSchema($this->loadedFiles["xsd"]);

        $schema->addSchema($newSchema);

        $callbacks = $this->loadSchema($newSchema, $file);

        return function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };

    }

    public function addGlobalSchemas(Schema $rootSchema)
    {
        $preload = array(
            "xsd" => __DIR__ . '/res/XMLSchema.xsd',
            "xml" => __DIR__ . '/res/xml.xsd'
        );

        $callbacksAll = array();

        foreach($preload as $key => $filePath){
            if(!isset($this->loadedFiles[$filePath])){
                $this->loadedFiles[$key] = $this->loadedFiles[$filePath] = $schema = new Schema();
                $schema->setFile($filePath);
                $rootSchema->addSchema($schema);
                $callbacks = $this->loadSchema($schema, $filePath);

                $callbacksAll[] = function() use ($callbacks) {
                    array_map('call_user_func', $callbacks);
                };
            }
        }

        if(!$this->loadedFiles["xml"]->getSchemas()){
            $this->loadedFiles["xml"]->addSchema($this->loadedFiles["xsd"]);
        }
        if(!$this->loadedFiles["xsd"]->getSchemas()){
            $this->loadedFiles["xsd"]->addSchema($this->loadedFiles["xml"]);
        }
        return $callbacksAll;
    }
    private $loadedFiles = array();

    public function readSchema($file)
    {
        if (isset($this->loadedFiles[$file])) {
            return $this->loadedFiles[$file];
        }

        $this->loadedFiles[$file] = $rootSchema = new Schema();
        $rootSchema->setFile($file);


        $callbacksAll = array();

        $callbacks = $this->addGlobalSchemas($rootSchema);

        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };

        $callbacks = $this->loadSchema($rootSchema, $file);
        $callbacksAll[] = function() use ($callbacks) {
            array_map('call_user_func', $callbacks);
        };


        array_map('call_user_func', $callbacksAll);

        return $rootSchema;
    }
    protected function loadSchema(Schema $schema, $file)
    {

        $xml = $this->loadDOM($file);

        return $this->schemaNode($schema, $xml->documentElement);
    }


}
