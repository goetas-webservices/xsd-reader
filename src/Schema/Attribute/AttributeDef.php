<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class AttributeDef extends Item implements AttributeItem
{
    /**
     * @var static|null
     */
    protected $fixed;

    /**
     * @var static|null
     */
    protected $default;

    /**
     * @return static|null
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @param static $fixed
     *
     * @return $this
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * @return static|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param static $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
