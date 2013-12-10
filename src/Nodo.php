<?php
namespace goetas\xml\xsd;
abstract class Nodo
{
    /**
     * @var Schema
     */
    protected $xsd;
    /**
     * @var Type
     */
    protected $type;
    /**
     * @var string
     */
    protected $name;

    protected $qualification = null;

    public function __construct(Schema $xsd, Type $type, $name, $qualification = null)
    {
        $this->xsd = $xsd;
        $this->type = $type;
        $this->name = $name;
        $this->qualification = $qualification;
    }
    abstract public function getQualification();
    public function getNs()
    {
        return $this->xsd->getNs();
    }
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return \goetas\xml\xsd\Type
     */
    public function getType()
    {
        return $this->type;
    }
    public function __toString()
    {
        return "{".$this->getNs()."}#$this->name";
    }
}
