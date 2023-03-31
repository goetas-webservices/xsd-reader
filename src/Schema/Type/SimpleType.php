<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

class SimpleType extends Type
{
    /**
     * @var SimpleType[]
     */
    protected array $unions = [];

    protected ?SimpleType $list = null;

    public function addUnion(self $type): void
    {
        $this->unions[] = $type;
    }

    /**
     * @return SimpleType[]
     */
    public function getUnions(): array
    {
        return $this->unions;
    }

    public function getList(): ?self
    {
        return $this->list;
    }

    public function setList(self $list): void
    {
        $this->list = $list;
    }
}
