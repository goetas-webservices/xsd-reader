<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

interface MetaInformationContainerInterface
{
    /**
     * @param list<MetaInformation> $meta
     */
    public function setMeta(array $meta): void;

    /**
     * @return list<MetaInformation>
     */
    public function getMeta(): array;
}
