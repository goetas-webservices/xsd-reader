<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;

interface ElementContainer extends SchemaItem
{

    public function addElement(ElementItem $element);

    public function getElements();
}
