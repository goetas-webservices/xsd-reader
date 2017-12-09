<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItemTrait;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;

abstract class Type implements SchemaItem
{
    use SchemaItemTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var bool
     */
    protected $abstract = false;

    /**
     * @var Restriction|null
     */
    protected $restriction;

    /**
     * @var Extension|null
     */
    protected $extension;

    public function __construct(Schema $schema, string $name = null)
    {
        $this->name = $name ?: null;
        $this->schema = $schema;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    public function __toString() : string
    {
        return strval($this->name);
    }

    public function isAbstract() : bool
    {
        return $this->abstract;
    }

    /**
     * @return $this
     */
    public function setAbstract(bool $abstract) : Type
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * @return Restriction|Extension|null
     */
    public function getParent()
    {
        return $this->restriction ?: $this->extension;
    }

    /**
     * @return Restriction|null
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @return $this
     */
    public function setRestriction(Restriction $restriction) : Type
    {
        $this->restriction = $restriction;

        return $this;
    }

    /**
     * @return Extension|null
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @return $this
     */
    public function setExtension(Extension $extension) : Type
    {
        $this->extension = $extension;

        return $this;
    }
}
