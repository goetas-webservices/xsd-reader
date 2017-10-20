<?php
namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Exception\TypeNotFoundException;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Base;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Extension;
use GoetasWebservices\XML\XSDReader\Schema\Inheritance\Restriction;
use GoetasWebservices\XML\XSDReader\Schema\Item;
use GoetasWebservices\XML\XSDReader\Schema\Schema;
use GoetasWebservices\XML\XSDReader\Schema\SchemaItem;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\ComplexTypeSimpleContent;
use GoetasWebservices\XML\XSDReader\Schema\Type\SimpleType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use GoetasWebservices\XML\XSDReader\Utils\UrlUtils;
use RuntimeException;

abstract class SchemaReaderCallbackAbstraction extends AbstractSchemaReader
{
    /**
    * @param mixed[][] $methods
    *
    * @return mixed
    */
    protected function maybeCallCallableWithArgs(
        DOMElement $childNode,
        array $commonMethods = [],
        array $methods = [],
        array $commonArguments = []
    ) {
        foreach ($commonMethods as $commonMethodsSpec) {
            list ($localNames, $callable, $args) = $commonMethodsSpec;

            if (in_array($childNode->localName, $localNames)) {
                return call_user_func_array($callable, $args);
            }
        }
        foreach ($commonArguments as $commonArgumentSpec) {
            list ($callables, $args) = $commonArgumentSpec;

            if (isset($callables[$childNode->localName])) {
                return call_user_func_array(
                    $callables[$childNode->localName],
                    $args
                );
            }
        }
        if (isset($methods[$childNode->localName])) {
            list ($callable, $args) = $methods[$childNode->localName];

            return call_user_func_array($callable, $args);
        }
    }

    protected function maybeLoadSequenceFromElementContainer(
        BaseComplexType $type,
        DOMElement $childNode
    ) {
        if (! ($type instanceof ElementContainer)) {
            throw new RuntimeException(
                '$type passed to ' .
                __FUNCTION__ .
                'expected to be an instance of ' .
                ElementContainer::class .
                ' when child node localName is "group", ' .
                get_class($type) .
                ' given.'
            );
        }
        $this->loadSequence($type, $childNode);
    }

    /**
    * @param Closure|null $callback
    *
    * @return Closure
    */
    protected function makeCallbackCallback(
        Type $type,
        DOMElement $node,
        Closure $callbackCallback,
        $callback = null
    ) {
        return function (
        ) use (
            $type,
            $node,
            $callbackCallback,
            $callback
        ) {
            $this->runCallbackAgainstDOMNodeList(
                $type,
                $node,
                $callbackCallback,
                $callback
            );
        };
    }

    /**
    * @param Closure|null $callback
    */
    protected function runCallbackAgainstDOMNodeList(
        Type $type,
        DOMElement $node,
        Closure $againstNodeList,
        $callback = null
    ) {
        $this->fillTypeNode($type, $node, true);

        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $againstNodeList(
                    $node,
                    $childNode
                );
            }
        }

        if ($callback) {
            call_user_func($callback, $type);
        }
    }

    protected function maybeLoadExtensionFromBaseComplexType(
        Type $type,
        DOMElement $childNode
    ) {
        if (! ($type instanceof BaseComplexType)) {
            throw new RuntimeException(
                'Argument 1 passed to ' .
                __METHOD__ .
                ' needs to be an instance of ' .
                BaseComplexType::class .
                ' when passed onto ' .
                static::class .
                '::loadExtension(), ' .
                get_class($type) .
                ' given.'
            );
        }
        $this->loadExtension($type, $childNode);
    }
}
