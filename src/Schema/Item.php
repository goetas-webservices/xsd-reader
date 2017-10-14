<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

abstract class Item implements SchemaItem
{
    /**
    * @var string|null
    */
    protected $doc;

    /**
    * @var Schema
    */
    protected $schema;

    /**
    * @var string
    */
    protected $name;

    /**
    * @var Type|null
    */
    protected $type;

    /**
    * @param string $name
    */
    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }

    /**
    * @return string
    */
    public function getName()
    {
        return $this->name;
    }

    /**
    * @param string $name
    *
    * @return $this
    */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     *
     * @return Type|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
    * @return $this
    */
    public function setType(Type $type)
    {
        $this->type = $type;
        return $this;
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
    *
    * @return $this
    */
    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

    /**
    * @return Schema
    */
    public function getSchema()
    {
        return $this->schema;
    }
}
