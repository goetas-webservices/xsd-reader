<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

interface AttributeSingle extends AttributeItem
{
    const USE_OPTIONAL = 'optional';

    const USE_PROHIBITED = 'prohibited';

    const USE_REQUIRED = 'required';

    /**
     * @return \GoetasWebservices\XML\XSDReader\Schema\Type\Type|null
     */
    public function getType();

    public function isQualified(): bool;

    public function setQualified(bool $qualified);

    public function isNil(): bool;

    public function setNil(bool $nil);

    public function getUse(): string;

    public function setUse(string $use);
}
