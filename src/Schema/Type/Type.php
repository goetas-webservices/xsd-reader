<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Schema;
abstract class Type
{
    protected $schema;

    protected $name;

    protected $doc;

    public function __construct(Schema $schema, $name = null)
    {
        $this->name = $name;
        $this->schema = $schema;
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

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }
    /**
     *
     * @return Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }
    public function __toString()
    {
        return strval($this->name);
    }

}
