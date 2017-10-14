<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Item;

class AttributeRef extends Item implements AttributeSingle
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
     *
     * @var AttributeDef
     */
    protected $wrapped;

    public function __construct(AttributeDef $att)
    {
        parent::__construct($att->getSchema(), $att->getName());
        $this->wrapped = $att;
    }
    /**
     *
     * @return AttributeDef
     */
    public function getReferencedAttribute()
    {
        return $this->wrapped;
    }

    /**
    * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type|null
    */
    public function getType()
    {
        return $this->wrapped->getType();
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
