<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;

class SimpleType extends Type
{
    /**
     * @var Restriction|null
     */
    protected $restriction;

    /**
     * @var SimpleType[]
     */
    protected $unions = array();

    /**
     * @var SimpleType|null
     */
    protected $list;

    /**
     * @return Restriction|null
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @return $this
     */
    public function setRestriction(Restriction $restriction): Type
    {
        $this->restriction = $restriction;

        return $this;
    }

    /**
     * @return $this
     */
    public function addUnion(self $type): self
    {
        $this->unions[] = $type;

        return $this;
    }

    /**
     * @return SimpleType[]
     */
    public function getUnions(): array
    {
        return $this->unions;
    }

    /**
     * @return SimpleType|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @return $this
     */
    public function setList(self $list): self
    {
        $this->list = $list;

        return $this;
    }
}
