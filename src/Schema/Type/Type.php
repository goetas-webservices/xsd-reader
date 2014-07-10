<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Schema;
abstract class Type
{
    protected $schema;

    protected $name;

    protected $doc;

    public function __construct($name = null)
    {
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
    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
        return $this;
    }

}
