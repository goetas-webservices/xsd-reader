<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Attribute\Attribute;
use Goetas\XML\XSDReader\Schema\Attribute\AttributeHolder;

abstract class BaseComplexType extends Type implements AttributeHolder
{

    /**
     *
     * @var Type
     */
    protected $restrict;

    /**
     *
     * @var Type
     */
    protected $extends;

    protected $attributes = array();


    public function set()
    {
        $this->elements = array();
        $this->attributes = array();
    }
    public function getParent()
    {
        return $this->restrict ?  : $this->extends;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }

    public function getRestrict()
    {
        return $this->restrict;
    }

    public function setRestrict(Type $restrict)
    {
        $this->restrict = $restrict;
        return $this;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function setExtends(Type $extends)
    {
        $this->extends = $extends;
        return $this;
    }
    public function getAttributes()
    {
        return $this->attributes;
    }
    private static function navAttributes(Attribute $attr){
        $attrs = array();
        if ($attr instanceof AttributeHolder) {
            foreach ($attr->getAttributes() as $attrExtra) {
                $attrs = array_merge($attrs, self::navAttributes($attrExtra));
            }
        } else {
            $attrs[] = $attr;
        }
        return $attrs;

    }
    public function getAllAttributes()
    {
        $attrs = array();
        foreach ($this->getAttributes() as $attr) {
            foreach (self::navAttributes($attr) as $attrExtra) {
                $attrs[] = $attrExtra;
            }
        }
        return $attrs;
    }
}
