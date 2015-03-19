<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

abstract class Item implements SchemaItem
{

    protected $doc;

    protected $schema;

    protected $name;

    protected $type;

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
    /**
     *
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
        return $this;
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
