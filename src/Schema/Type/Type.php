<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Schema;
use Goetas\XML\XSDReader\Schema\SchemaItem;
use Goetas\XML\XSDReader\Schema\Inheritance\Extension;
use Goetas\XML\XSDReader\Schema\Inheritance\Restriction;
abstract class Type implements SchemaItem
{
    protected $schema;

    protected $name;

    protected $abstract = false;

    protected $doc;

    /**
     *
     * @var Restriction
     */
    protected $restriction;

    /**
     *
     * @var Extension
     */
    protected $extension;

    public function __construct(Schema $schema, $name = null)
    {
        $this->name = $name?:null;
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

    public function isAbstract()
    {
        return $this->abstract;
    }

    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     *
     * @return \Goetas\XML\XSDReader\Schema\Inheritance\Base
    */
    public function getParent()
    {
        return $this->restriction ?  : $this->extension;
    }

    public function getRestriction()
    {
        return $this->restriction;
    }

    public function setRestriction(Restriction $restriction)
    {
        $this->restriction = $restriction;
        return $this;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;
        return $this;
    }
}
