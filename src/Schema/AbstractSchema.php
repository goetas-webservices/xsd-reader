<?php

namespace GoetasWebservices\XML\XSDReader\Schema;

use Closure;
use DOMElement;
use RuntimeException;
use GoetasWebservices\XML\XSDReader\AbstractSchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReader;
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

abstract class AbstractSchema
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
    protected $typeCache = array();

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
     * @param string $name
     *
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
     * @param string $name
     *
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
     * @param string $name
     *
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
     * @param string $name
     *
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
     * @param string $name
     *
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
        return sprintf('Target namespace %s', $this->getTargetNamespace());
    }

    /**
     * @param string $getter
     * @param string $name
     * @param string $namespace
     * @param bool[] $calling
     * @param bool   $throw
     *
     * @return SchemaItem|null
     */
    abstract protected function findSomethingNoThrow(
        $getter,
        $name,
        $namespace = null,
        array &$calling = array()
    );

    /**
     * @param Schema[] $schemas
     * @param string   $cid
     * @param string   $getter
     * @param string   $name
     * @param string   $namespace
     * @param bool[]   $calling
     * @param bool     $throw
     *
     * @return SchemaItem|null
     */
    abstract protected function findSomethingNoThrowSchemas(
        array $schemas,
        $cid,
        $getter,
        $name,
        $namespace = null,
        array &$calling = array()
    );

    /**
     * @param string $getter
     * @param string $name
     * @param string $namespace
     * @param bool[] $calling
     * @param bool   $throw
     *
     * @throws TypeNotFoundException
     *
     * @return SchemaItem
     */
    abstract protected function findSomething(
        $getter,
        $name,
        $namespace = null,
        &$calling = array()
    );

    /**
     * @param string $name
     * @param string $namespace
     *
     * @return Type
     */
    public function findType($name, $namespace = null)
    {
        /**
         * @var Type
         */
        $out = $this->findSomething('getType', $name, $namespace);

        return $out;
    }

    /**
     * @param string $name
     * @param string $namespace
     *
     * @return Group
     */
    public function findGroup($name, $namespace = null)
    {
        /**
         * @var Group
         */
        $out = $this->findSomething('getGroup', $name, $namespace);

        return $out;
    }

    /**
     * @param string $name
     * @param string $namespace
     *
     * @return ElementDef
     */
    public function findElement($name, $namespace = null)
    {
        /**
         * @var ElementDef
         */
        $out = $this->findSomething('getElement', $name, $namespace);

        return $out;
    }

    /**
     * @param string $name
     * @param string $namespace
     *
     * @return AttributeItem
     */
    public function findAttribute($name, $namespace = null)
    {
        /**
         * @var AttributeItem
         */
        $out = $this->findSomething('getAttribute', $name, $namespace);

        return $out;
    }

    /**
     * @param string $name
     * @param string $namespace
     *
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
    protected static $loadedFiles = array();

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

        if ($node->hasAttribute('targetNamespace')) {
            $this->setTargetNamespace($node->getAttribute('targetNamespace'));
        } elseif ($parent) {
            $this->setTargetNamespace($parent->getTargetNamespace());
        }
        $this->setElementsQualification($node->getAttribute('elementFormDefault') == 'qualified');
        $this->setAttributesQualification($node->getAttribute('attributeFormDefault') == 'qualified');
        $this->setDoc(AbstractSchemaReader::getDocumentation($node));
    }

    /**
     * @param string $file
     * @param string $namespace
     *
     * @return Closure
     */
    public static function loadImport(
        SchemaReader $reader,
        Schema $schema,
        DOMElement $node
    ) {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute('schemaLocation'));

        $namespace = $node->getAttribute('namespace');

        $keys = static::loadImportFreshKeys($reader, $namespace, $file);

        if (
            static::hasLoadedFile(...$keys)
        ) {
            $schema->addSchema(static::getLoadedFile(...$keys));

            return function () {
            };
        }

        return static::loadImportFresh($namespace, $reader, $schema, $file);
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return mixed[]
     */
    abstract protected static function loadImportFreshKeys(
        SchemaReader $reader,
        $namespace,
        $file
    );

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Schema
     */
    abstract protected static function loadImportFreshCallbacksNewSchema(
        $namespace,
        SchemaReader $reader,
        Schema $schema,
        $file
    );

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure[]
     */
    abstract protected static function loadImportFreshCallbacks(
        $namespace,
        SchemaReader $reader,
        Schema $schema,
        $file
    );

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure
     */
    abstract protected static function loadImportFresh(
        $namespace,
        SchemaReader $reader,
        Schema $schema,
        $file
    );
}
