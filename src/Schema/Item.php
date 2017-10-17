<?php
namespace GoetasWebservices\XML\XSDReader\Schema;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItemTrait;

abstract class Item implements SchemaItem
{
    use SchemaItemTrait;

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
}
