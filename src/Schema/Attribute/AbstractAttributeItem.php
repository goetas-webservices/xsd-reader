<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;

abstract class AbstractAttributeItem extends Item implements AttributeItem
{
    /**
     * @var string|null
     */
    protected $fixed;

    /**
     * @var string|null
     */
    protected $default;

    /**
     * @return string|null
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * @param string $fixed
     *
     * @return $this
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param string $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
