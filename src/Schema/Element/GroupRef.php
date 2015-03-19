<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class GroupRef extends Group
{

    protected $wrapped;

    protected $min = 1;

    protected $max = 1;

    public function __construct(Group $group)
    {
        parent::__construct($group->getSchema(), '');
        $this->wrapped = $group;
    }

    public function getMin()
    {
        return $this->min;
    }

    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }

    public function getMax()
    {
        return $this->max;
    }

    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }

    public function getName()
    {
        return $this->wrapped->getName();
    }

    public function setName($name)
    {
        throw new \Exception("Can't set the name for a ref group");
    }

    public function getElements()
    {
        $elements = $this->wrapped->getElements();
        if($this->getMax()>0 || $this->getMax()===-1){
            foreach ($elements as $k => $element) {
                $e = clone $element;
                $e->setMax($this->getMax());
                $elements[$k] = $e;
            }
        }
        return $elements;
    }

    public function addElement(ElementItem $element)
    {
        throw new \Exception("Can't set the name for a ref group");
    }
}
