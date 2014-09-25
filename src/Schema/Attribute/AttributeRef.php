<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;

class AttributeRef extends TypeNodeChild implements AttributeItem
{

    protected $qualified = true;

    protected $nil = false;

    protected $use = self::USE_OPTIONAL;

    protected $wrapped;

    public function __construct(Attribute $att)
    {
        parent::__construct($att->getSchema(), $att->getName());
        $this->wrapped = $att;
    }
    public function isAnonymousType()
    {
        return $this->wrapped->isAnonymousType();
    }
    public function getType()
    {
        return $this->wrapped->getType();
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
