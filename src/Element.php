<?php
namespace goetas\xml\xsd;
class Element extends Nodo
{
    protected $nillable = false;

    protected $min = 0;
    protected $max;

    public function __construct(Schema $xsd, Type $type, $name, $min = 0, $max = PHP_INT_MAX, $nillable=false, $qualification = null)
    {
        parent::__construct($xsd, $type, $name, $qualification);
        $this->min = $min;
        $this->max = $max;
        $this->nillable = $nillable;

    }
    public function getMin()
    {
        return $this->min;
    }
    public function getMax()
    {
        return $this->max;
    }
    public function isNillable()
    {
        return $this->nillable;
    }
    public function getQualification()
    {
        if ($this->qualification) {
            return $this->qualification;
        }

        return $this->xsd->getElementQualification();
    }

}
