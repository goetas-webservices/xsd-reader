<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

class Attribute extends AbstractAttributeItem implements AttributeSingle
{
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

    public function isQualified() : bool
    {
        return $this->qualified;
    }

    /**
     * @return $this
     */
    public function setQualified(bool $qualified) : Attribute
    {
        $this->qualified = $qualified;

        return $this;
    }

    public function isNil() : bool
    {
        return $this->nil;
    }

    /**
     * @return $this
     */
    public function setNil(bool $nil) : Attribute
    {
        $this->nil = $nil;

        return $this;
    }

    public function getUse() : string
    {
        return $this->use;
    }

    /**
     * @return $this
     */
    public function setUse(string $use) : Attribute
    {
        $this->use = $use;

        return $this;
    }
}
