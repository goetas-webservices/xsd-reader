<?php

namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMElement;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
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
use RuntimeException;

class SchemaReader extends AbstractSchemaReader
{
    /**
     * @param string $typeName
     *
     * @return mixed[]
     */
    protected static function splitParts(DOMElement $node, $typeName)
    {
        $prefix = null;
        $name = $typeName;
        if (strpos($typeName, ':') !== false) {
            list($prefix, $name) = explode(':', $typeName);
        }

        $namespace = $node->lookupNamespaceUri($prefix ?: '');

        return array(
            $name,
            $namespace,
            $prefix,
        );
    }

    /**
     * @param bool $attributeDef
     *
     * @return Closure
     */
    protected function loadAttributeOrElementDef(
        Schema $schema,
        DOMElement $node,
        $attributeDef
    ) {
        $name = $node->getAttribute('name');
        if ($attributeDef) {
            $attribute = new AttributeDef($schema, $name);
            $schema->addAttribute($attribute);
        } else {
            $attribute = new ElementDef($schema, $name);
            $schema->addElement($attribute);
        }

        return function () use ($attribute, $node) {
            $this->fillItem($attribute, $node);
        };
    }

    /**
     * @return Closure
     */
    protected function loadAttributeDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, true);
    }

    /**
     * @param int|null $max
     *
     * @return int|null
     */
    protected static function loadSequenceNormaliseMax(DOMElement $node, $max)
    {
        return
        (
            (is_int($max) && (bool) $max) ||
            $node->getAttribute('maxOccurs') == 'unbounded' ||
            $node->getAttribute('maxOccurs') > 1
        )
            ? 2
            : null;
    }

    /**
     * @param int|null $max
     */
    protected function loadSequence(ElementContainer $elementContainer, DOMElement $node, $max = null)
    {
        $max = static::loadSequenceNormaliseMax($node, $max);

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $elementContainer,
                $max
            ) {
                $this->loadSequenceChildNode(
                    $elementContainer,
                    $node,
                    $childNode,
                    $max
                );
            }
        );
    }

    /**
     * @param int|null $max
     */
    protected function loadSequenceChildNode(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        $commonMethods = [
            [
                ['sequence', 'choice', 'all'],
                [$this, 'loadSequenceChildNodeLoadSequence'],
                [
                    $elementContainer,
                    $childNode,
                    $max,
                ],
            ],
        ];
        $methods = [
            'element' => [
                [$this, 'loadSequenceChildNodeLoadElement'],
                [
                    $elementContainer,
                    $node,
                    $childNode,
                    $max,
                ],
            ],
            'group' => [
                [$this, 'loadSequenceChildNodeLoadGroup'],
                [
                    $elementContainer,
                    $node,
                    $childNode,
                ],
            ],
        ];

        $this->maybeCallCallableWithArgs($childNode, $commonMethods, $methods);
    }

    /**
     * @param int|null $max
     */
    protected function loadSequenceChildNodeLoadSequence(
        ElementContainer $elementContainer,
        DOMElement $childNode,
        $max
    ) {
        $this->loadSequence($elementContainer, $childNode, $max);
    }

    /**
     * @param int|null $max
     */
    protected function loadSequenceChildNodeLoadElement(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode,
        $max
    ) {
        if ($childNode->hasAttribute('ref')) {
            /**
             * @var ElementDef $referencedElement
             */
            $referencedElement = $this->findSomething('findElement', $elementContainer->getSchema(), $node, $childNode->getAttribute('ref'));
            $element = ElementRef::loadElementRef(
                $referencedElement,
                $childNode
            );
        } else {
            $element = Element::loadElement(
                $this,
                $elementContainer->getSchema(),
                $childNode
            );
        }
        if (is_int($max) && (bool) $max) {
            $element->setMax($max);
        }
        $elementContainer->addElement($element);
    }

    protected function loadSequenceChildNodeLoadGroup(
        ElementContainer $elementContainer,
        DOMElement $node,
        DOMElement $childNode
    ) {
        $this->addGroupAsElement(
            $elementContainer->getSchema(),
            $node,
            $childNode,
            $elementContainer
        );
    }

    protected function addGroupAsElement(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        ElementContainer $elementContainer
    ) {
        /**
         * @var Group
         */
        $referencedGroup = $this->findSomething(
            'findGroup',
            $schema,
            $node,
            $childNode->getAttribute('ref')
        );

        $group = GroupRef::loadGroupRef($referencedGroup, $childNode);
        $elementContainer->addElement($group);
    }

    /**
     * @return Closure
     */
    protected function loadGroup(Schema $schema, DOMElement $node)
    {
        return Group::loadGroup($this, $schema, $node);
    }

    /**
     * @return BaseComplexType
     */
    protected function loadComplexTypeBeforeCallbackCallback(
        Schema $schema,
        DOMElement $node
    ) {
        /**
         * @var bool
         */
        $isSimple = false;

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                &$isSimple
            ) {
                if ($isSimple) {
                    return;
                }
                if ($childNode->localName === 'simpleContent') {
                    $isSimple = true;
                }
            }
        );

        $type = $isSimple ? new ComplexTypeSimpleContent($schema, $node->getAttribute('name')) : new ComplexType($schema, $node->getAttribute('name'));

        $type->setDoc(static::getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return $type;
    }

    /**
     * @param Closure|null $callback
     *
     * @return Closure
     */
    protected function loadComplexType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = $this->loadComplexTypeBeforeCallbackCallback($schema, $node);

        return $this->makeCallbackCallback(
            $type,
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schema,
                $type
            ) {
                $this->loadComplexTypeFromChildNode(
                    $type,
                    $node,
                    $childNode,
                    $schema
                );
            },
            $callback
        );
    }

    protected function loadComplexTypeFromChildNode(
        BaseComplexType $type,
        DOMElement $node,
        DOMElement $childNode,
        Schema $schema
    ) {
        $commonMethods = [
            [
                ['sequence', 'choice', 'all'],
                [$this, 'maybeLoadSequenceFromElementContainer'],
                [
                    $type,
                    $childNode,
                ],
            ],
        ];
        $methods = [
            'attribute' => [
                [$type, 'addAttributeFromAttributeOrRef'],
                [
                    $this,
                    $childNode,
                    $schema,
                    $node,
                ],
            ],
            'attributeGroup' => [
                (AttributeGroup::class.'::findSomethingLikeThis'),
                [
                    $this,
                    $schema,
                    $node,
                    $childNode,
                    $type,
                ],
            ],
        ];
        if (
            $type instanceof ComplexType
        ) {
            $methods['group'] = [
                [$this, 'addGroupAsElement'],
                [
                    $schema,
                    $node,
                    $childNode,
                    $type,
                ],
            ];
        }

        $this->maybeCallCallableWithArgs($childNode, $commonMethods, $methods);
    }

    /**
     * @param Closure|null $callback
     *
     * @return Closure
     */
    protected function loadSimpleType(Schema $schema, DOMElement $node, $callback = null)
    {
        $type = new SimpleType($schema, $node->getAttribute('name'));
        $type->setDoc(static::getDocumentation($node));
        if ($node->getAttribute('name')) {
            $schema->addType($type);
        }

        return $this->makeCallbackCallback(
            $type,
            $node,
            $this->CallbackGeneratorMaybeCallMethodAgainstDOMNodeList(
                $type,
                [
                    'union' => 'loadUnion',
                    'list' => 'loadList',
                ]
            ),
            $callback
        );
    }

    protected function loadList(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute('itemType')) {
            /**
             * @var SimpleType
             */
            $listType = $this->findSomeType($type, $node, 'itemType');
            $type->setList($listType);
        } else {
            $addCallback = function (SimpleType $list) use ($type) {
                $type->setList($list);
            };

            Type::loadTypeWithCallbackOnChildNodes(
                $this,
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
    }

    protected function loadUnion(SimpleType $type, DOMElement $node)
    {
        if ($node->hasAttribute('memberTypes')) {
            $types = preg_split('/\s+/', $node->getAttribute('memberTypes'));
            foreach ($types as $typeName) {
                /**
                 * @var SimpleType
                 */
                $unionType = $this->findSomeTypeFromAttribute(
                    $type,
                    $node,
                    $typeName
                );
                $type->addUnion($unionType);
            }
        }
        $addCallback = function (SimpleType $unType) use ($type) {
            $type->addUnion($unType);
        };

        Type::loadTypeWithCallbackOnChildNodes(
            $this,
            $type->getSchema(),
            $node,
            $addCallback
        );
    }

    protected function loadExtensionChildNodes(
        BaseComplexType $type,
        DOMElement $node
    ) {
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $type
            ) {
                $commonMethods = [
                    [
                        ['sequence', 'choice', 'all'],
                        [$this, 'maybeLoadSequenceFromElementContainer'],
                        [
                            $type,
                            $childNode,
                        ],
                    ],
                ];
                $methods = [
                    'attribute' => [
                        [$type, 'addAttributeFromAttributeOrRef'],
                        [
                            $this,
                            $childNode,
                            $type->getSchema(),
                            $node,
                        ],
                    ],
                    'attributeGroup' => [
                        (AttributeGroup::class.'::findSomethingLikeThis'),
                        [
                            $this,
                            $type->getSchema(),
                            $node,
                            $childNode,
                            $type,
                        ],
                    ],
                ];

                $this->maybeCallCallableWithArgs(
                    $childNode,
                    $commonMethods,
                    $methods
                );
            }
        );
    }

    protected function loadExtension(BaseComplexType $type, DOMElement $node)
    {
        $extension = new Extension();
        $type->setExtension($extension);

        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase(
                $type,
                $extension,
                $node
            );
        }
        $this->loadExtensionChildNodes($type, $node);
    }

    protected function loadRestriction(Type $type, DOMElement $node)
    {
        Restriction::loadRestriction($this, $type, $node);
    }

    /**
     * @return Closure
     */
    protected function loadElementDef(Schema $schema, DOMElement $node)
    {
        return $this->loadAttributeOrElementDef($schema, $node, false);
    }

    /**
     * @param bool $checkAbstract
     */
    protected function fillTypeNode(Type $type, DOMElement $node, $checkAbstract = false)
    {
        if ($checkAbstract) {
            $type->setAbstract($node->getAttribute('abstract') === 'true' || $node->getAttribute('abstract') === '1');
        }
        static $methods = [
            'restriction' => 'loadRestriction',
            'extension' => 'maybeLoadExtensionFromBaseComplexType',
            'simpleContent' => 'fillTypeNode',
            'complexContent' => 'fillTypeNode',
        ];

        /**
         * @var string[]
         */
        $methods = $methods;

        $this->maybeCallMethodAgainstDOMNodeList($node, $type, $methods);
    }

    protected function fillItemNonLocalType(Item $element, DOMElement $node)
    {
        if ($node->getAttribute('type')) {
            /**
             * @var Type
             */
            $type = $this->findSomeType($element, $node, 'type');
        } else {
            /**
             * @var Type
             */
            $type = $this->findSomeTypeFromAttribute(
                $element,
                $node,
                ($node->lookupPrefix(self::XSD_NS).':anyType')
            );
        }

        $element->setType($type);
    }

    /**
     * @param string $attributeName
     *
     * @return SchemaItem
     */
    protected function findSomeType(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        return $this->findSomeTypeFromAttribute(
            $fromThis,
            $node,
            $node->getAttribute($attributeName)
        );
    }

    /**
     * @param string $attributeName
     *
     * @return SchemaItem
     */
    protected function findSomeTypeFromAttribute(
        SchemaItem $fromThis,
        DOMElement $node,
        $attributeName
    ) {
        /**
         * @var SchemaItem
         */
        $out = $this->findSomething(
            'findType',
            $fromThis->getSchema(),
            $node,
            $attributeName
        );

        return $out;
    }

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
