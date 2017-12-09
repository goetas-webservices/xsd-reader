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

    /**
     * @param string|null $name
     */
    public function __construct(Schema $schema, $name = null)
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

    public function __toString()
    {
        return strval($this->name);
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param bool $abstract
     *
     * @return $this
     */
    public function setAbstract($abstract)
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
    public function setRestriction(Restriction $restriction)
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
    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;

        return $this;
    }
}
