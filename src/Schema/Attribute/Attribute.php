<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;

interface Attribute
{
    const USE_OPTIONAL = 'optional';
    const USE_PROHIBITED = 'prohibited';
    const USE_REQUIRED = 'required';

    public function getName();
}
