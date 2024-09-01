<?php

namespace GoetasWebservices\XML\XSDReader\Schema\Element\Any;

enum ProcessContents: string
{
    case Strict = 'strict';
    case Lax = 'lax';
    case Skip = 'skip';

    public static function default(): self
    {
        return self::Strict;
    }
}
