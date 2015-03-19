<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
class SimpleType extends Type
{

    /**
     *
     * @var Restriction
     */
    protected $restriction;

    /**
     *
     * @var SimpleType[]
     */
    protected $unions = array();

    /**
     *
     * @var SimpleType
     */
    protected $list;

    /**
     *
     * @return Restriction
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    public function setRestriction(Restriction $restriction)
    {
        $this->restriction = $restriction;
        return $this;
    }

    public function addUnion(SimpleType $type)
    {
        $this->unions[] = $type;
        return $this;
    }

    public function getUnions()
    {
        return $this->unions;
    }

    /**
     *
     * @return SimpleType
     */
    public function getList()
    {
        return $this->list;
    }

    public function setList(SimpleType $list)
    {
        $this->list = $list;
        return $this;
    }
}
