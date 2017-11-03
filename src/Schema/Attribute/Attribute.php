<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class Attribute extends Item implements AttributeSingle
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
     * @var bool
     */
    protected $qualified = true;

    /**
     * @var bool
     */
    protected $nil = false;

    /**
     * @var string
     */
    protected $use = self::USE_OPTIONAL;

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

    /**
     * @return bool
     */
    public function isQualified()
    {
        return $this->qualified;
    }

    /**
     * @param bool $qualified
     *
     * @return $this
     */
    public function setQualified($qualified)
    {
        $this->qualified = $qualified;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNil()
    {
        return $this->nil;
    }

    /**
     * @param bool $nil
     *
     * @return $this
     */
    public function setNil($nil)
    {
        $this->nil = $nil;

        return $this;
    }

    /**
     * @return string
     */
    public function getUse()
    {
        return $this->use;
    }

    /**
     * @param string $use
     *
     * @return $this
     */
    public function setUse($use)
    {
        $this->use = $use;

        return $this;
    }
}
