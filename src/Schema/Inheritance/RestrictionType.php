<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Inheritance;

enum RestrictionType: string
{
    case ENUMERATION = 'enumeration';
    case LENGTH = 'length';
    case FRACTION_DIGITS = 'fractionDigits';
    case MAX_EXCLUSIVE = 'maxExclusive';
    case MIN_EXCLUSIVE = 'minExclusive';
    case MAX_INCLUSIVE = 'maxInclusive';
    case MIN_INCLUSIVE = 'minInclusive';
    case MAX_LENGTH = 'maxLength';
    case MIN_LENGTH = 'minLength';
    case PATTERN = 'pattern';
    case TOTAL_DIGITS = 'totalDigits';
    case WHITE_SPACE = 'whiteSpace';
}
