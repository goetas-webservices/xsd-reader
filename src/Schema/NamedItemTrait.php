<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema;

trait NamedItemTrait
{
    /**
     * @var string
     */
    protected $name;

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }
}
