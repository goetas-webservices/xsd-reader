<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;

class AttributeGroup implements Attribute, AttributeHolder
{

    protected $doc;

    protected $name;

    protected $attributes = array();

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[] = $attribute;
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

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

}
