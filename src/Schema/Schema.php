<?php
namespace Goetas\XML\XSDReader\Schema;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Element\Element;
use Goetas\XML\XSDReader\Schema\Type\SimpleType;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeGroup;
use Goetas\XML\XSDReader\Schema\Attribute\Attribute;
use Goetas\XML\XSDReader\Schema\Element\Group;
use Goetas\XML\XSDReader\Exception\TypeException;
use Goetas\XML\XSDReader\Schema\Element\ElementNode;

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

    public function __construct()
    {
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

    /**
     *
     * @param Type $type
     *            $types
     */
    public function addType(Type $type)
    {
        $type->setSchema($this);
        $this->types[$type->getName()] = $type;
    }

    /**
     *
     * @param Element $elements
     */
    public function addElement(ElementNode $element)
    {
        $this->elements[] = $element;
    }

    /**
     *
     * @param unknown_type $thiss
     */
    public function addSchema(Schema $schema)
    {
        if (strlen(trim($schema->getTargetNamespace()))) {
            $this->schemas[$this->getTargetNamespace()] = $schema;
        } else {
            $this->schemas[] = $schema;
        }
    }

    /**
     *
     * @param unknown_type $attributes
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    /**
     *
     * @param unknown_type $groups
     */
    public function addGroup(Group $group)
    {
        $this->groups[] = $group;
    }

    /**
     *
     * @param unknown_type $groups
     */
    public function addAttributeGroup(AttributeGroup $group)
    {
        $this->attributeGroups[] = $group;
    }

    public function getAttributeGroups()
    {
        return $this->attributeGroups;
    }

    public function __toString()
    {
        return "TNS: " . $this->targetNamespace . " in '{$this->file}'";
    }

    public function getDebugTypes()
    {
        $sch = $this->schemas;
        unset($sch["http://www.w3.org/2001/XMLSchema"]);

        $s = "Types in $this - :\nSchemas: " . implode(", ", array_keys($sch)) . "\nTypes:  " . implode(", ", array_keys($this->types));

        foreach ($sch as $schema) {
            if ($schema !== $this) {
                $s .= "\n\n + " . $schema->getDebugTypes();
            }
        }

        return $s;
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

    private $typeCache = array();

    public function findType($name, $namespace = null)
    {
        $cid = "$name, $namespace";
        if (isset($this->typeCache[$cid])) {
            return $this->typeCache[$cid];
        }
        if (! $this->getTargetNamespace() || $this->getTargetNamespace() === $namespace) {
            foreach ($this->getTypes() as $item) {
                if ($item->getName() === $name) {
                    return $this->typeCache[$cid] = $item;
                }
            }
        }
        foreach ($this->getSchemas() as $childSchema) {
            if (! $childSchema->getTargetNamespace() || $childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $this->typeCache[$cid] = $childSchema->findType($name, $namespace);
                } catch (TypeException $e) {
                }
            }
        }
        throw new TypeException("Non trovo tipo $name{{$namespace}} .\nTipi dispo: " . $this->getDebugTypes());
    }

    /**
     *
     * @param Schema $this
     * @param DOMElement $node
     * @param string $name{{$namespace}}
     * @throws TypeException
     * @return Group
     */
    public function findGroup($name, $namespace = null)
    {
        if (! $this->getTargetNamespace() || $this->getTargetNamespace() === $namespace) {
            foreach ($this->getGroups() as $item) {
                if ($item->getName() === $name) {
                    return $item;
                }
            }
        }

        foreach ($this->getSchemas() as $childSchema) {
            if (! $childSchema->getTargetNamespace() || $childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $childSchema->findGroup($name, $namespace);
                } catch (TypeException $e) {
                }
            }
        }
        throw new TypeException("Non trovo il gruppo $name{{$namespace}} .\nTipi dispo: " . $this->getDebugTypes());
    }

    /**
     *
     * @param Schema $this
     * @param DOMElement $node
     * @param string $name{{$namespace}}
     * @throws TypeException
     * @return Group
     */
    public function findElement($name, $namespace = null)
    {
        if (! $this->getTargetNamespace() || $this->getTargetNamespace() === $namespace) {
            foreach ($this->getElements() as $item) {
                if ($item->getName() === $name) {
                    return $item;
                }
            }
        }

        foreach ($this->getSchemas() as $childSchema) {
            if (! $childSchema->getTargetNamespace() || $childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $childSchema->findElement($name, $namespace);
                } catch (TypeException $e) {
                }
            }
        }
        throw new TypeException("Non trovo il gruppo $name{{$namespace}} .\nTipi dispo: " . $this->getDebugTypes());
    }

    /**
     *
     * @param Schema $this
     * @param DOMElement $node
     * @param string $name{{$namespace}}
     * @throws TypeException
     * @return Attribute
     */
    public function findAttribute($name, $namespace = null)
    {
        if (! $this->getTargetNamespace() || $this->getTargetNamespace() === $namespace) {
            foreach ($this->getAttributes() as $item) {
                if ($item->getName() === $name) {
                    return $item;
                }
            }
        }

        foreach ($this->getSchemas() as $childSchema) {
            if (! $childSchema->getTargetNamespace() || $childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $childSchema->findAttribute($name, $namespace);
                } catch (TypeException $e) {
                }
            }
        }
        throw new TypeException("Non trovo l'attributo $name{{$namespace}} .\nTipi dispo: " . $this->getDebugTypes());
    }

    /**
     *
     * @param Schema $this
     * @param DOMElement $node
     * @param string $name{{$namespace}}
     * @throws TypeException
     * @return Attribute
     */
    public function findAttributeGroup($name, $namespace = null)
    {
        if (! $this->getTargetNamespace() || $this->getTargetNamespace() === $namespace) {
            foreach ($this->getAttributeGroups() as $item) {
                if ($item->getName() === $name) {
                    return $item;
                }
            }
        }

        foreach ($this->getSchemas() as $childSchema) {
            if (! $childSchema->getTargetNamespace() || $childSchema->getTargetNamespace() === $namespace) {
                try {
                    return $childSchema->findAttributeGroup($name, $namespace);
                } catch (TypeException $e) {
                }
            }
        }
        throw new TypeException("Non trovo grupo di attributi $name{{$namespace}} .\nTipi dispo: " . $this->getDebugTypes());
    }
}
