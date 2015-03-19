<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Schema;

class Group implements ElementItem, ElementContainer
{

    /**
     *
     * @var Schema
     */
    protected $schema;

    protected $doc;

    protected $name;

    protected $elements = array();

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

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(ElementItem $element)
    {
        $this->elements[] = $element;
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
