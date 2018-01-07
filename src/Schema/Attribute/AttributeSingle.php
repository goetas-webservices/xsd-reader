<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

interface AttributeSingle extends AttributeItem
{
    const USE_OPTIONAL = 'optional';

    const USE_PROHIBITED = 'prohibited';

    const USE_REQUIRED = 'required';

    public function getType(): ?Type;

    public function isQualified(): bool;

    public function setQualified(bool $qualified): void;

    public function isNil(): bool;

    public function setNil(bool $nil): void;

    public function getUse(): ?string;

    public function setUse(string $use): void;
}
