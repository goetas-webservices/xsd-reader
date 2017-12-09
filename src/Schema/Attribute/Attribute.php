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
