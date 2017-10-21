<?php
namespace GoetasWebservices\XML\XSDReader\Schema\Type;

use Closure;
use DOMNode;
use DOMElement;
use GoetasWebservices\XML\XSDReader\AbstractSchemaReader;
use GoetasWebservices\XML\XSDReader\SchemaReader;
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
     *
     * @var Restriction|null
     */
    protected $restriction;

    /**
     *
     * @var Extension|null
     */
    protected $extension;

    /**
    * @param string|null $name
    */
    public function __construct(Schema $schema, $name = null)
    {
        $this->name = $name?:null;
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
     *
     * @return Restriction|Extension|null
    */
    public function getParent()
    {
        return $this->restriction ?  : $this->extension;
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

    public static function loadTypeWithCallbackOnChildNodes(
        AbstractSchemaReader $schemaReader,
        Schema $schema,
        DOMNode $node,
        Closure $callback
    ) {
        AbstractSchemaReader::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schemaReader,
                $schema,
                $callback
            ) {
                static::loadTypeWithCallback(
                    $schemaReader,
                    $schema,
                    $childNode,
                    $callback
                );
            }
        );
    }

    public static function loadTypeWithCallback(
        AbstractSchemaReader $schemaReader,
        Schema $schema,
        DOMNode $childNode,
        Closure $callback
    ) {
        if (! ($childNode instanceof DOMElement)) {
            return;
        }
        $methods = [
            'complexType' => 'loadComplexType',
            'simpleType' => 'loadSimpleType',
        ];

        /**
        * @var Closure|null $func
        */
        $func = $schemaReader->maybeCallMethod(
            $methods,
            $childNode->localName,
            $childNode,
            $schema,
            $childNode,
            $callback
        );

        if ($func instanceof Closure) {
            call_user_func($func);
        }
    }
}
