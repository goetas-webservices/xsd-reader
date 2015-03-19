<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Exception\SchemaException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;

class Schema
{

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

    public function addElement(ElementDef $element)
    {
        $this->elements[$element->getName()] = $element;
    }

    public function addSchema(Schema $schema, $namespace = null)
    {
        if ($namespace !== null && $schema->getTargetNamespace() !== $namespace) {
            throw new SchemaException(sprintf("The target namespace ('%s') for schema, does not match the declared namespace '%s'", $schema->getTargetNamespace(), $namespace));
        }

        if ($namespace !== null) {
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
        return sprintf("Target namespaace %s", $this->getTargetNamespace());
    }

    /**
     *
     * @param string $getter
     * @param string $name
     * @param string $namespace
     * @throws TypeNotFoundException
     * @return \GoetasWebservices\XML\XSDReader\Schema\SchemaItem
     */
    protected function findSomething($getter, $name, $namespace = null, &$calling = array())
    {
        $calling[spl_object_hash($this)] = true;
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
            if ($childSchema->getTargetNamespace() === $namespace && !isset($calling[spl_object_hash($childSchema)])) {
                try {
                    return $this->typeCache[$cid] = $childSchema->findSomething($getter, $name, $namespace, $calling);
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
     * @return ElementDef
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
