<?php
namespace goetas\xml\xsd;
class Attribute extends Nodo
{
    protected $required = false;
    protected $default;
    public function __construct(Schema $xsd, SimpleType $type, $name, $required = false, $default = null, $qualification  = null)
    {
        parent::__construct($xsd, $type, $name, $qualification);
        $this->required = $required;
        $this->default = $default;
    }
    public function getQualification()
    {
        if ($this->qualification) {
            return $this->qualification;
        }

        return $this->xsd->getAttributeQualification();
    }
    public function getDefault()
    {
        return $this->default;
    }
    public function isRequred()
    {
        return (bool) $this->required;
    }
}
