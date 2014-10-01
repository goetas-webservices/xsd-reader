<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

class Attribute extends AttributeDef implements AttributeSingle
{

    protected $qualified = true;

    protected $nil = false;

    protected $use = self::USE_OPTIONAL;

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
