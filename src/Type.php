<?php
namespace goetas\xml\xsd;

abstract class Type
{
    const NS = 'http://www.w3.org/2001/XMLSchema';
    /**
     * @var goetas\xml\xsd\Schema
     */
    protected $xsd;

    protected $name;
    protected $anonymous= false;

    public function __construct(Schema $xsd, $name)
    {
        $this->xsd = $xsd;
        $this->name = $name;
    }

    /**
     * @return goetas\xml\xsd\Schema
     */
    public function getSchema()
    {
        return $this->xsd;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function getNs()
    {
        return $this->getSchema()->getNs();
    }
    public function __toString()
    {
        return "{".$this->getNs()."}". $this->getName();
    }
    public function getAnonymous()
    {
        return $this->anonymous;
    }

    public function setAnonymous($anonymous = true)
    {
        $this->anonymous = $anonymous;
    }

}
