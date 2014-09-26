<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\SchemaItem;

interface AttributeSingle extends AttributeItem
{

    const USE_OPTIONAL = 'optional';

    const USE_PROHIBITED = 'prohibited';

    const USE_REQUIRED = 'required';

    public function getType();

}
