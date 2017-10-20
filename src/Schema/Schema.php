<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

use Closure;
use DOMElement;
use RuntimeException;
use GoetasWebservices\XML\XSDReader\AbstractSchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReaderLoadAbstraction;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Exception\SchemaException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;

class Schema
{
    /**
    * @var bool
    */
    protected $elementsQualification = false;

    /**
    * @var bool
    */
    protected $attributesQualification = false;

    /**
    * @var string|null
    */
    protected $targetNamespace;

    /**
    * @var Schema[]
    */
    protected $schemas = array();

    /**
    * @var Type[]
    */
    protected $types = array();

    /**
    * @var ElementDef[]
    */
    protected $elements = array();

    /**
    * @var Group[]
    */
    protected $groups = array();

    /**
    * @var AttributeGroup[]
    */
    protected $attributeGroups = array();

    /**
    * @var AttributeDef[]
    */
    protected $attributes = array();

    /**
    * @var string|null
    */
    protected $doc;

    /**
    * @var \GoetasWebservices\XML\XSDReader\Schema\SchemaItem[]
    */
    private $typeCache = array();

    /**
    * @return bool
    */
    public function getElementsQualification()
    {
        return $this->elementsQualification;
    }

    /**
    * @param bool $elementsQualification
    */
    public function setElementsQualification($elementsQualification)
    {
        $this->elementsQualification = $elementsQualification;
    }

    /**
    * @return bool
    */
    public function getAttributesQualification()
    {
        return $this->attributesQualification;
    }

    /**
    * @param bool $attributesQualification
    */
    public function setAttributesQualification($attributesQualification)
    {
        $this->attributesQualification = $attributesQualification;
    }

    /**
    * @return string|null
    */
    public function getTargetNamespace()
    {
        return $this->targetNamespace;
    }

    /**
    * @param string|null $targetNamespace
    */
    public function setTargetNamespace($targetNamespace)
    {
        $this->targetNamespace = $targetNamespace;
    }

    /**
    * @return Type[]
    */
    public function getTypes()
    {
        return $this->types;
    }

    /**
    * @return ElementDef[]
    */
    public function getElements()
    {
        return $this->elements;
    }

    /**
    * @return Schema[]
    */
    public function getSchemas()
    {
        return $this->schemas;
    }

    /**
    * @return AttributeDef[]
    */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
    * @return Group[]
    */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
    * @return string|null
    */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
    * @param string $doc
    */
    public function setDoc($doc)
    {
        $this->doc = $doc;
    }

    public function addType(Type $type)
    {
        $this->types[$type->getName()] = $type;
    }

    public function addElement(ElementDef $element)
    {
        $this->elements[$element->getName()] = $element;
    }

    /**
    * @param string|null $namespace
    */
    public function addSchema(Schema $schema, $namespace = null)
    {
        if ($namespace !== null) {
            if ($schema->getTargetNamespace() !== $namespace) {
                throw new SchemaException(
                    sprintf(
                        "The target namespace ('%s') for schema, does not match the declared namespace '%s'",
                        $schema->getTargetNamespace(),
                        $namespace
                    )
                );
            }
            $this->schemas[$namespace] = $schema;
        } else {
            $this->schemas[] = $schema;
        }
    }

    public function addAttribute(AttributeDef $attribute)
    {
        $this->attributes[$attribute->getName()] = $attribute;
    }

    public function addGroup(Group $group)
    {
        $this->groups[$group->getName()] = $group;
    }

    public function addAttributeGroup(AttributeGroup $group)
    {
        $this->attributeGroups[$group->getName()] = $group;
    }

    /**
    * @return AttributeGroup[]
    */
    public function getAttributeGroups()
    {
        return $this->attributeGroups;
    }

    /**
     *
     * @param string $name
     * @return Group|false
     */
    public function getGroup($name)
    {
        if (isset($this->groups[$name])) {
            return $this->groups[$name];
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return ElementItem|false
     */
    public function getElement($name)
    {
        if (isset($this->elements[$name])) {
            return $this->elements[$name];
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return Type|false
     */
    public function getType($name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return AttributeItem|false
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return false;
    }

    /**
     *
     * @param string $name
     * @return AttributeGroup|false
     */
    public function getAttributeGroup($name)
    {
        if (isset($this->attributeGroups[$name])) {
            return $this->attributeGroups[$name];
        }
        return false;
    }

    public function __toString()
    {
        return sprintf("Target namespace %s", $this->getTargetNamespace());
    }

    /**
    * @param string $getter
    * @param string $name
    * @param string $namespace
    * @param bool[] $calling
    * @param bool $throw
    *
    * @return SchemaItem|null
    */
    protected function findSomethingNoThrow(
        $getter,
        $name,
        $namespace = null,
        array & $calling = array()
    ) {
        $calling[spl_object_hash($this)] = true;
        $cid = "$getter, $name, $namespace";

        if (isset($this->typeCache[$cid])) {
            return $this->typeCache[$cid];
        }

        if (null === $namespace || $this->getTargetNamespace() === $namespace) {
            /**
            * @var \GoetasWebservices\XML\XSDReader\Schema\SchemaItem|null $item
            */
            $item = $this->$getter($name);
            if ($item instanceof SchemaItem) {
                return $this->typeCache[$cid] = $item;
            }
        }

        foreach ($this->getSchemas() as $childSchema) {
            if (!isset($calling[spl_object_hash($childSchema)])) {
                $in = $childSchema->findSomethingNoThrow($getter, $name, $namespace, $calling);

                if ($in instanceof SchemaItem) {
                    return $this->typeCache[$cid] = $in;
                }
            }
        }
    }

    /**
     *
     * @param string $getter
     * @param string $name
     * @param string $namespace
     * @param bool[] $calling
     * @param bool $throw
     *
     * @throws TypeNotFoundException
     * @return SchemaItem
     */
    protected function findSomething($getter, $name, $namespace = null, &$calling = array())
    {
        $in = $this->findSomethingNoThrow(
            $getter,
            $name,
            $namespace,
            $calling
        );

        if ($in instanceof SchemaItem) {
            return $in;
        }

        throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", substr($getter, 3), $namespace, $name));
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return Type
     */
    public function findType($name, $namespace = null)
    {
        /**
        * @var Type $out
        */
        $out = $this->findSomething('getType', $name, $namespace);

        return $out;
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return Group
     */
    public function findGroup($name, $namespace = null)
    {
        /**
        * @var Group $out
        */
        $out = $this->findSomething('getGroup', $name, $namespace);

        return $out;
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return ElementDef
     */
    public function findElement($name, $namespace = null)
    {
        /**
        * @var ElementDef $out
        */
        $out = $this->findSomething('getElement', $name, $namespace);

        return $out;
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return AttributeItem
     */
    public function findAttribute($name, $namespace = null)
    {
        /**
        * @var AttributeItem $out
        */
        $out = $this->findSomething('getAttribute', $name, $namespace);

        return $out;
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return AttributeGroup
     */
    public function findAttributeGroup($name, $namespace = null)
    {
        /**
        * @var AttributeGroup
        */
        $out = $this->findSomething('getAttributeGroup', $name, $namespace);

        return $out;
    }

    /**
    * @var Schema[]
    */
    private static $loadedFiles = array();

    /**
    * @param string ...$keys
    *
    * @return bool
    */
    public static function hasLoadedFile(...$keys)
    {
        foreach ($keys as $key) {
            if (isset(self::$loadedFiles[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
    * @param string ...$keys
    *
    * @return Schema
    *
    * @throws RuntimeException if loaded file not found
    */
    public static function getLoadedFile(...$keys)
    {
        foreach ($keys as $key) {
            if (isset(self::$loadedFiles[$key])) {
        return self::$loadedFiles[$key];
            }
        }

        throw new RuntimeException('Loaded file was not found!');
    }

    /**
    * @param string $key
    *
    * @return Schema
    */
    public static function setLoadedFile($key, Schema $schema)
    {
        self::$loadedFiles[$key] = $schema;

        return $schema;
    }

    public function setSchemaThingsFromNode(
        DOMElement $node,
        Schema $parent = null
    ) {
        $this->setDoc(AbstractSchemaReader::getDocumentation($node));

        if ($node->hasAttribute("targetNamespace")) {
            $this->setTargetNamespace($node->getAttribute("targetNamespace"));
        } elseif ($parent) {
            $this->setTargetNamespace($parent->getTargetNamespace());
        }
        $this->setElementsQualification($node->getAttribute("elementFormDefault") == "qualified");
        $this->setAttributesQualification($node->getAttribute("attributeFormDefault") == "qualified");
        $this->setDoc(AbstractSchemaReader::getDocumentation($node));
    }

    /**
    * @param string $file
    * @param string $namespace
    *
    * @return Closure
    */
    public static function loadImport(
        SchemaReaderLoadAbstraction $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute("schemaLocation"));

        $namespace = $node->getAttribute("namespace");

        $globalSchemaInfo = $reader->getGlobalSchemaInfo();

        $keys = [];

        if (isset($globalSchemaInfo[$namespace])) {
            $keys[] = $globalSchemaInfo[$namespace];
        }

        $keys[] = $reader->getNamespaceSpecificFileIndex(
            $file,
            $namespace
        );

        $keys[] = $file;

        if (
            Schema::hasLoadedFile(...$keys)
        ) {
            $schema->addSchema(Schema::getLoadedFile(...$keys));

            return function() {
            };
        }

        $newSchema = Schema::setLoadedFile(
            $file,
            ($namespace ? new Schema() : $schema)
        );

        if ($namespace) {
            $newSchema->addSchema($reader->getGlobalSchema());
            $schema->addSchema($newSchema);
        }


        return function () use ($newSchema, $reader, $schema, $file) {
            $callbacks = $reader->schemaNode(
                $newSchema,
                $reader->getDOM(
                    $reader->hasKnownSchemaLocation($file)
                        ? $reader->getKnownSchemaLocation($file)
                        : $file
                )->documentElement,
                $schema
            );
            foreach ($callbacks as $callback) {
                $callback();
            }
        };
    }
}
