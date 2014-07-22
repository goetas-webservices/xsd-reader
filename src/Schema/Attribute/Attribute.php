<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;
use Goetas\XML\XSDReader\Schema\SchemaItem;

interface Attribute extends SchemaItem
{
    const USE_OPTIONAL = 'optional';
    const USE_PROHIBITED = 'prohibited';
    const USE_REQUIRED = 'required';

    public function getName();
}
