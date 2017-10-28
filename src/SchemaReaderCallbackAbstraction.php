<?php

namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Type\BaseComplexType;
use GoetasWebservices\XML\XSDReader\Schema\Type\Type;
use RuntimeException;

abstract class SchemaReaderCallbackAbstraction extends AbstractSchemaReader
{
    /**
     * @param mixed[][] $commonMethods
     * @param mixed[][] $methods
     * @param mixed[][] $commonArguments
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
            list($localNames, $callable, $args) = $commonMethodsSpec;

            /**
             * @var string[]
             */
            $localNames = $localNames;

            /**
             * @var callable
             */
            $callable = $callable;

            /**
             * @var mixed[]
             */
            $args = $args;

            if (in_array($childNode->localName, $localNames)) {
                return call_user_func_array($callable, $args);
            }
        }
        foreach ($commonArguments as $commonArgumentSpec) {
            /*
            * @var mixed[] $commonArgumentSpec
            */
            list($callables, $args) = $commonArgumentSpec;

            /**
             * @var callable[]
             */
            $callables = $callables;

            /**
             * @var mixed[]
             */
            $args = $args;

            if (isset($callables[$childNode->localName])) {
                return call_user_func_array(
                    $callables[$childNode->localName],
                    $args
                );
            }
        }
        if (isset($methods[$childNode->localName])) {
            list($callable, $args) = $methods[$childNode->localName];

            /**
             * @var callable
             */
            $callable = $callable;

            /**
             * @var mixed[]
             */
            $args = $args;

            return call_user_func_array($callable, $args);
        }
    }

    protected function maybeLoadSequenceFromElementContainer(
        BaseComplexType $type,
        DOMElement $childNode
    ) {
        $this->maybeLoadThingFromThing(
            $type,
            $childNode,
            ElementContainer::class,
            'loadSequence'
        );
    }

    /**
     * @param string $instanceof
     * @param string $passTo
     */
    protected function maybeLoadThingFromThing(
        Type $type,
        DOMElement $childNode,
        $instanceof,
        $passTo
    ) {
        if (!is_a($type, $instanceof, true)) {
            /**
             * @var string
             */
            $class = static::class;
            throw new RuntimeException(
                'Argument 1 passed to '.
                __METHOD__.
                ' needs to be an instance of '.
                $instanceof.
                ' when passed onto '.
                $class.
                '::'.
                $passTo.
                '(), '.
                (string) get_class($type).
                ' given.'
            );
        }

        $this->$passTo($type, $childNode);
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

        static::againstDOMNodeList($node, $againstNodeList);

        if ($callback) {
            call_user_func($callback, $type);
        }
    }

    protected function maybeLoadExtensionFromBaseComplexType(
        Type $type,
        DOMElement $childNode
    ) {
        $this->maybeLoadThingFromThing(
            $type,
            $childNode,
            BaseComplexType::class,
            'loadExtension'
        );
    }
}
