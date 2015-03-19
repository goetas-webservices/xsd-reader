<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group implements AttributeItem, AttributeContainer
{

    /**
     *
     * @var Schema
     */
    protected $schema;

    protected $doc;

    protected $name;

    protected $attributes = array();

    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function addAttribute(AttributeItem $attribute)
    {
        $this->attributes[] = $attribute;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

    public function getSchema()
    {
        return $this->schema;
    }
}
