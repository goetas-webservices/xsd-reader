<?php

declare(strict_types=1);

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
    /**
     * @param bool[] $calling
     *
     * @return SchemaItem|null
     */
    protected function findSomethingNoThrow(
        string $getter,
        string $name,
        string $namespace = null,
        array &$calling = array()
    ) {
        $calling[spl_object_hash($this)] = true;
        $cid = "$getter, $name, $namespace";

        if (isset($this->typeCache[$cid])) {
            return $this->typeCache[$cid];
        } elseif (
            $this->getTargetNamespace() === $namespace
        ) {
            /**
             * @var SchemaItem|null
             */
            $item = $this->$getter($name);

            if ($item instanceof SchemaItem) {
                return $this->typeCache[$cid] = $item;
            }
        }

        return $this->findSomethingNoThrowSchemas(
            $this->getSchemas(),
            $cid,
            $getter,
            $name,
            $namespace,
            $calling
        );
    }

    /**
     * @param Schema[] $schemas
     * @param bool[]   $calling
     *
     * @return SchemaItem|null
     */
    protected function findSomethingNoThrowSchemas(
        array $schemas,
        string $cid,
        string $getter,
        string $name,
        string $namespace = null,
        array &$calling = array()
    ) {
        foreach ($schemas as $childSchema) {
            if (!isset($calling[spl_object_hash($childSchema)])) {
                /**
                 * @var SchemaItem|null
                 */
                $in = $childSchema->findSomethingNoThrow($getter, $name, $namespace, $calling);

                if ($in instanceof SchemaItem) {
                    return $this->typeCache[$cid] = $in;
                }
            }
        }
    }

    /**
     * @throws TypeNotFoundException
     */
    protected function findSomething(string $getter, string $name, string $namespace = null, array &$calling = array()): SchemaItem
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

        throw new TypeNotFoundException(
            sprintf(
                "Can't find the %s named {%s}#%s.",
                (string) substr($getter, 3),
                $namespace,
                $name
            )
        );
    }

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

    public function getElementsQualification(): bool
    {
        return $this->elementsQualification;
    }

    public function setElementsQualification(bool $elementsQualification)
    {
        $this->elementsQualification = $elementsQualification;
    }

    public function getAttributesQualification(): bool
    {
        return $this->attributesQualification;
    }

    public function setAttributesQualification(bool $attributesQualification)
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
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @return ElementDef[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @return Schema[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * @return AttributeDef[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
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

    public function setDoc(string $doc)
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

    public function addSchema(self $schema, string $namespace = null)
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
    public function getAttributeGroups(): array
    {
        return $this->attributeGroups;
    }

    /**
     * @return Group|false
     */
    public function getGroup(string $name)
    {
        if (isset($this->groups[$name])) {
            return $this->groups[$name];
        }

        return false;
    }

    /**
     * @return ElementItem|false
     */
    public function getElement(string $name)
    {
        if (isset($this->elements[$name])) {
            return $this->elements[$name];
        }

        return false;
    }

    /**
     * @return Type|false
     */
    public function getType(string $name)
    {
        if (isset($this->types[$name])) {
            return $this->types[$name];
        }

        return false;
    }

    /**
     * @return AttributeItem|false
     */
    public function getAttribute(string $name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return false;
    }

    /**
     * @return AttributeGroup|false
     */
    public function getAttributeGroup(string $name)
    {
        if (isset($this->attributeGroups[$name])) {
            return $this->attributeGroups[$name];
        }

        return false;
    }

    public function __toString(): string
    {
        return sprintf('Target namespace %s', $this->getTargetNamespace());
    }

    public function findType(string $name, string $namespace = null): Type
    {
        $out = $this->findSomething('getType', $name, $namespace);

        if (!($out instanceof Type)) {
            throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", 'Type', $namespace, $name));
        }

        return $out;
    }

    public function findGroup(string $name, string $namespace = null): Group
    {
        $out = $this->findSomething('getGroup', $name, $namespace);

        if (!($out instanceof Group)) {
            throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", 'Group', $namespace, $name));
        }

        return $out;
    }

    public function findElement(string $name, string $namespace = null): ElementDef
    {
        $out = $this->findSomething('getElement', $name, $namespace);

        if (!($out instanceof ElementDef)) {
            throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", 'Element', $namespace, $name));
        }

        return $out;
    }

    public function findAttribute(string $name, string $namespace = null): AttributeItem
    {
        $out = $this->findSomething('getAttribute', $name, $namespace);

        if (!($out instanceof AttributeItem)) {
            throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", 'Attribute', $namespace, $name));
        }

        return $out;
    }

    public function findAttributeGroup(string $name, string $namespace = null): AttributeGroup
    {
        $out = $this->findSomething('getAttributeGroup', $name, $namespace);

        if (!($out instanceof AttributeGroup)) {
            throw new TypeNotFoundException(sprintf("Can't find the %s named {%s}#%s.", 'AttributeGroup', $namespace, $name));
        }

        return $out;
    }
}
