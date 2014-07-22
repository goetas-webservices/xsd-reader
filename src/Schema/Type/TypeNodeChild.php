<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Schema;

abstract class TypeNodeChild
{
    protected $doc;

    protected $schema;

    protected $name;

    protected $type;

    protected $isAnonymousType = false;



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

    public function getType()
    {
        return $this->type;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
        return $this;
    }

    public function isAnonymousType()
    {
        return $this->isAnonymousType;
    }

    public function setIsAnonymousType($isAnonymousType)
    {
        $this->isAnonymousType = !!$isAnonymousType;
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
