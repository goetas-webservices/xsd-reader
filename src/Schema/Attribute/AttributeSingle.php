<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Attribute;

use GoetasWebservices\XML\XSDReader\Schema\CustomAttributeContainerInterface;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;

interface AttributeSingle extends AttributeItem, CustomAttributeContainerInterface
{
    public const USE_OPTIONAL = 'optional';

    public const USE_PROHIBITED = 'prohibited';

    public const USE_REQUIRED = 'required';

    public function getType(): ?Type;

    public function getFixed(): ?string;

    public function setFixed(string $fixed): void;

    public function getDefault(): ?string;

    public function setDefault(string $default): void;

    public function isQualified(): bool;

    public function setQualified(bool $qualified): void;

    public function isNil(): bool;

    public function setNil(bool $nil): void;

    public function getUse(): ?string;

    public function setUse(string $use): void;
}
