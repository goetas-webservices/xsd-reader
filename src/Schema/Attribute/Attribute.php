<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class Attribute extends Item implements AttributeSingle
{

    protected $fixed;

    protected $default;

    protected $qualified = true;

    protected $nil = false;

    protected $use = self::USE_OPTIONAL;

    public function getFixed()
    {
        return $this->fixed;
    }

    public function setFixed($fixed)
    {
        $this->fixed = $fixed;
        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    public function isQualified()
    {
        return $this->qualified;
    }

    public function setQualified($qualified)
    {
        $this->qualified = $qualified;
        return $this;
    }

    public function isNil()
    {
        return $this->nil;
    }

    public function setNil($nil)
    {
        $this->nil = $nil;
        return $this;
    }

    public function getUse()
    {
        return $this->use;
    }

    public function setUse($use)
    {
        $this->use = $use;
        return $this;
    }
}
