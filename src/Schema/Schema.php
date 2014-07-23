<?php
namespace Goetas\XML\XSDReader\Schema;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeGroup;
use Goetas\XML\XSDReader\Schema\Attribute\Attribute;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeReal;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Element\ElementNode;
use Goetas\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use Goetas\XML\XSDReader\Schema\Exception\SchemaException;


class Schema
{

    protected $file;

    protected $elementsQualification = true;

    protected $attributesQualification = false;

    protected $targetNamespace;

    protected $schemas = array();

    protected $types = array();

    protected $elements = array();

    protected $groups = array();

    protected $attributeGroups = array();

    protected $attributes = array();

    protected $doc;

    private $typeCache = array();

    public function __construct($schemaLocation)
    {
        $this->file = $schemaLocation;
    }

    public function getElementsQualification()
    {
        return $this->elementsQualification;
    }

    public function setElementsQualification($elementsQualification)
    {
        $this->elementsQualification = $elementsQualification;
    }

    public function getAttributesQualification()
    {
        return $this->attributesQualification;
    }

    public function setAttributesQualification($attributesQualification)
    {
        $this->attributesQualification = $attributesQualification;
    }

    public function getTargetNamespace()
    {
        return $this->targetNamespace;
    }

    public function setTargetNamespace($targetNamespace)
    {
        $this->targetNamespace = $targetNamespace;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function getSchemas()
    {
        return $this->schemas;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
    }

    public function addType(Type $type)
    {
        $this->types[$type->getName()] = $type;
    }

    public function addElement(ElementNode $element)
    {
        $this->elements[$element->getName()] = $element;
    }

    public function addSchema(Schema $schema, $namespace = null)
    {
        if ($namespace !== null && $schema->getTargetNamespace() !== $namespace) {
            throw new SchemaException(sprintf("The target namespace ('%s') for schema '%s', does not match the declared namespace '%s'", $schema->getTargetNamespace(), $schema->getFile(), $namespace));
        }

        if ($namespace !== null) {
            $this->schemas[$namespace] = $schema;
        } else {
            $this->schemas[] = $schema;
        }
    }

    public function addAttribute(AttributeReal $attribute)
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

    public function getAttributeGroups()
    {
        return $this->attributeGroups;
    }

    /**
     *
     * @param string $name
     * @return Group
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
     * @return ElementNode
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
     * @return Type
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
     * @return AttributeReal
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
     * @return AttributeGroup
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
        return sprintf("Schema %s, target namespaace %s", $this->getFile(), $this->getTargetNamespace());
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     *
     * @param string $getter
     * @param string $name
     * @param string $namespace
     * @throws TypeNotFoundException
     * @return \Goetas\XML\XSDReader\Schema\SchemaItem
     */
    protected function findSomething($getter, $name, $namespace = null)
    {
        $cid = "$getter, $name, $namespace";

        if (isset($this->typeCache[$cid])) {
            return $this->typeCache[$cid];
        }

        if (null === $namespace || $this->getTargetNamespace() === $namespace) {
            if ($item = $this->$getter($name)) {
                return $this->typeCache[$cid] = $item;
            }
        }
        foreach ($this->getSchemas() as $childSchema) {
            if ($childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $this->typeCache[$cid] = $childSchema->findSomething($getter, $name, $namespace);
                } catch (TypeNotFoundException $e) {
                }
            }
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
        return $this->findSomething('getType', $name, $namespace);
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return Group
     */
    public function findGroup($name, $namespace = null)
    {
        return $this->findSomething('getGroup', $name, $namespace);
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return ElementNode
     */
    public function findElement($name, $namespace = null)
    {
        return $this->findSomething('getElement', $name, $namespace);
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return AttributeReal
     */
    public function findAttribute($name, $namespace = null)
    {
        return $this->findSomething('getAttribute', $name, $namespace);
    }

    /**
     *
     * @param string $name
     * @param string $namespace
     * @return AttributeGroup
     */
    public function findAttributeGroup($name, $namespace = null)
    {
        return $this->findSomething('getAttributeGroup', $name, $namespace);
    }
}
