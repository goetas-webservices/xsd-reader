<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait MetaInformationContainerTrait
{
    /**
     * @var list<MetaInformation>
     */
    protected array $meta = [];

    /**
     * @return list<MetaInformation>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param list<MetaInformation> $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}
