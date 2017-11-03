<?php

namespace GoetasWebservices\XML\XSDReader;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use GoetasWebservices\XML\XSDReader\Exception\IOException;
use GoetasWebservices\XML\XSDReader\Exception\TypeException;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Attribute;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeContainer;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeDef;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\AttributeItem;
use GoetasWebservices\XML\XSDReader\Schema\Attribute\Group as AttributeGroup;
use GoetasWebservices\XML\XSDReader\Schema\Element\Element;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementContainer;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementDef;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementItem;
use GoetasWebservices\XML\XSDReader\Schema\Element\ElementRef;
use GoetasWebservices\XML\XSDReader\Schema\Element\Group;
use GoetasWebservices\XML\XSDReader\Schema\Element\InterfaceSetMinMax;
use GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef;
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

class SchemaReader
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
            $element = static::loadElementRef(
                $referencedElement,
                $childNode
            );
        } else {
            $element = $this->loadElement(
                $elementContainer->getSchema(),
                $childNode
            );
        }
        if ($max > 1) {
            /*
            * although one might think the typecast is not needed with $max being `? int $max` after passing > 1,
            * phpstan@a4f89fa still thinks it's possibly null.
            * see https://github.com/phpstan/phpstan/issues/577 for related issue
            */
            $element->setMax((int) $max);
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

        $group = $this->loadGroupRef($referencedGroup, $childNode);
        $elementContainer->addElement($group);
    }

    /**
     * @return Closure
     */
    protected function loadGroup(Schema $schema, DOMElement $node)
    {
        $group = static::loadGroupBeforeCheckingChildNodes(
            $schema,
            $node
        );
        static $methods = [
            'sequence' => 'loadSequence',
            'choice' => 'loadSequence',
            'all' => 'loadSequence',
        ];

        return function () use ($group, $node, $methods) {
            /**
             * @var string[]
             */
            $methods = $methods;
            $this->maybeCallMethodAgainstDOMNodeList(
                $node,
                $group,
                $methods
            );
        };
    }

    /**
     * @return Group|GroupRef
     */
    protected static function loadGroupBeforeCheckingChildNodes(
        Schema $schema,
        DOMElement $node
    ) {
        $group = new Group($schema, $node->getAttribute('name'));
        $group->setDoc(self::getDocumentation($node));

        if ($node->hasAttribute('maxOccurs')) {
            /**
             * @var GroupRef
             */
            $group = self::maybeSetMax(new GroupRef($group), $node);
        }
        if ($node->hasAttribute('minOccurs')) {
            /**
             * @var GroupRef
             */
            $group = self::maybeSetMin(
                $group instanceof GroupRef ? $group : new GroupRef($group),
                $node
            );
        }

        $schema->addGroup($group);

        return $group;
    }

    /**
     * @return GroupRef
     */
    public function loadGroupRef(Group $referenced, DOMElement $node)
    {
        $ref = new GroupRef($referenced);
        $ref->setDoc(self::getDocumentation($node));

        self::maybeSetMax($ref, $node);
        self::maybeSetMin($ref, $node);

        return $ref;
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
                [$this, 'addAttributeFromAttributeOrRef'],
                [
                    $type,
                    $childNode,
                    $schema,
                    $node,
                ],
            ],
            'attributeGroup' => [
                [$this, 'findSomethingLikeAttributeGroup'],
                [
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

            $this->loadTypeWithCallbackOnChildNodes(
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

        $this->loadTypeWithCallbackOnChildNodes(
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
                        [$this, 'addAttributeFromAttributeOrRef'],
                        [
                            $type,
                            $childNode,
                            $type->getSchema(),
                            $node,
                        ],
                    ],
                    'attributeGroup' => [
                        [$this, 'findSomethingLikeAttributeGroup'],
                        [
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
        $restriction = new Restriction();
        $type->setRestriction($restriction);
        if ($node->hasAttribute('base')) {
            $this->findAndSetSomeBase($type, $restriction, $node);
        } else {
            $addCallback = function (Type $restType) use ($restriction) {
                $restriction->setBase($restType);
            };

            $this->loadTypeWithCallbackOnChildNodes(
                $type->getSchema(),
                $node,
                $addCallback
            );
        }
        self::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $restriction
            ) {
                static::maybeLoadRestrictionOnChildNode(
                    $restriction,
                    $childNode
                );
            }
        );
    }

    protected static function maybeLoadRestrictionOnChildNode(
        Restriction $restriction,
        DOMElement $childNode
    ) {
        if (
            in_array(
                $childNode->localName,
                [
                    'enumeration',
                    'pattern',
                    'length',
                    'minLength',
                    'maxLength',
                    'minInclusive',
                    'maxInclusive',
                    'minExclusive',
                    'maxExclusive',
                    'fractionDigits',
                    'totalDigits',
                    'whiteSpace',
                ],
                true
            )
        ) {
            static::definitelyLoadRestrictionOnChildNode(
                $restriction,
                $childNode
            );
        }
    }

    protected static function definitelyLoadRestrictionOnChildNode(
        Restriction $restriction,
        DOMElement $childNode
    ) {
        $restriction->addCheck(
            $childNode->localName,
            [
                'value' => $childNode->getAttribute('value'),
                'doc' => self::getDocumentation($childNode),
            ]
        );
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

    const XSD_NS = 'http://www.w3.org/2001/XMLSchema';

    const XML_NS = 'http://www.w3.org/XML/1998/namespace';

    /**
     * @var string[]
     */
    protected $knownLocationSchemas = [
        'http://www.w3.org/2001/xml.xsd' => (
            __DIR__.'/Resources/xml.xsd'
        ),
        'http://www.w3.org/2001/XMLSchema.xsd' => (
            __DIR__.'/Resources/XMLSchema.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd' => (
            __DIR__.'/Resources/oasis-200401-wss-wssecurity-secext-1.0.xsd'
        ),
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd' => (
            __DIR__.'/Resources/oasis-200401-wss-wssecurity-utility-1.0.xsd'
        ),
        'https://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__.'/Resources/xmldsig-core-schema.xsd'
        ),
        'http://www.w3.org/TR/xmldsig-core/xmldsig-core-schema.xsd' => (
            __DIR__.'/Resources/xmldsig-core-schema.xsd'
        ),
    ];

    /**
     * @var string[]
     */
    protected static $globalSchemaInfo = array(
        self::XML_NS => 'http://www.w3.org/2001/xml.xsd',
        self::XSD_NS => 'http://www.w3.org/2001/XMLSchema.xsd',
    );

    /**
     * @param string $remote
     * @param string $local
     */
    public function addKnownSchemaLocation($remote, $local)
    {
        $this->knownLocationSchemas[$remote] = $local;
    }

    /**
     * @param string $remote
     *
     * @return bool
     */
    public function hasKnownSchemaLocation($remote)
    {
        return isset($this->knownLocationSchemas[$remote]);
    }

    /**
     * @param string $remote
     *
     * @return string
     */
    public function getKnownSchemaLocation($remote)
    {
        return $this->knownLocationSchemas[$remote];
    }

    /**
     * @param DOMElement $node
     *
     * @return string
     */
    public static function getDocumentation(DOMElement $node)
    {
        $doc = '';
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                &$doc
            ) {
                if ($childNode->localName == 'annotation') {
                    $doc .= static::getDocumentation($childNode);
                } elseif ($childNode->localName == 'documentation') {
                    $doc .= $childNode->nodeValue;
                }
            }
        );
        $doc = preg_replace('/[\t ]+/', ' ', $doc);

        return trim($doc);
    }

    /**
     * @param string[] $methods
     * @param string   $key
     *
     * @return Closure|null
     */
    public function maybeCallMethod(
        array $methods,
        $key,
        DOMNode $childNode,
        ...$args
    ) {
        if ($childNode instanceof DOMElement && isset($methods[$key])) {
            $method = $methods[$key];

            /**
             * @var Closure|null
             */
            $append = $this->$method(...$args);

            if ($append instanceof Closure) {
                return $append;
            }
        }
    }

    /**
     * @param Schema     $schema
     * @param DOMElement $node
     * @param Schema     $parent
     *
     * @return Closure[]
     */
    public function schemaNode(Schema $schema, DOMElement $node, Schema $parent = null)
    {
        $schema->setSchemaThingsFromNode($node, $parent);
        $functions = array();

        $thisMethods = [
            'attributeGroup' => [$this, 'loadAttributeGroup'],
            'include' => [$this, 'loadImport'],
            'import' => [$this, 'loadImport'],
            'element' => [$this, 'loadElementDef'],
            'attribute' => [$this, 'loadAttributeDef'],
            'group' => [$this, 'loadGroup'],
            'complexType' => [$this, 'loadComplexType'],
            'simpleType' => [$this, 'loadSimpleType'],
        ];

        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schema,
                $thisMethods,
                &$functions
            ) {
                /**
                 * @var Closure|null
                 */
                $callback = $this->maybeCallCallableWithArgs(
                    $childNode,
                    [],
                    [],
                    [
                        [
                            $thisMethods,
                            [
                                $schema,
                                $childNode,
                            ],
                        ],
                    ]
                );

                if ($callback instanceof Closure) {
                    $functions[] = $callback;
                }
            }
        );

        return $functions;
    }

    /**
     * @return InterfaceSetMinMax
     */
    public static function maybeSetMax(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if (
            $node->hasAttribute('maxOccurs')
        ) {
            $ref->setMax($node->getAttribute('maxOccurs') == 'unbounded' ? -1 : (int) $node->getAttribute('maxOccurs'));
        }

        return $ref;
    }

    /**
     * @return InterfaceSetMinMax
     */
    public static function maybeSetMin(InterfaceSetMinMax $ref, DOMElement $node)
    {
        if ($node->hasAttribute('minOccurs')) {
            $ref->setMin((int) $node->getAttribute('minOccurs'));
        }

        return $ref;
    }

    public function findAndSetSomeBase(
        Type $type,
        Base $setBaseOnThis,
        DOMElement $node
    ) {
        /**
         * @var Type
         */
        $parent = $this->findSomeType($type, $node, 'base');
        $setBaseOnThis->setBase($parent);
    }

    /**
     * @param string     $finder
     * @param Schema     $schema
     * @param DOMElement $node
     * @param string     $typeName
     *
     * @throws TypeException
     *
     * @return ElementItem|Group|AttributeItem|AttributeGroup|Type
     */
    public function findSomething($finder, Schema $schema, DOMElement $node, $typeName)
    {
        list($name, $namespace) = static::splitParts($node, $typeName);

        /**
         * @var string|null
         */
        $namespace = $namespace ?: $schema->getTargetNamespace();

        try {
            /**
             * @var ElementItem|Group|AttributeItem|AttributeGroup|Type
             */
            $out = $schema->$finder($name, $namespace);

            return $out;
        } catch (TypeNotFoundException $e) {
            throw new TypeException(sprintf("Can't find %s named {%s}#%s, at line %d in %s ", strtolower(substr($finder, 4)), $namespace, $name, $node->getLineNo(), $node->ownerDocument->documentURI), 0, $e);
        }
    }

    public function fillItem(Item $element, DOMElement $node)
    {
        /**
         * @var bool
         */
        $skip = false;
        static::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $element,
                &$skip
            ) {
                if (
                    !$skip &&
                    in_array(
                        $childNode->localName,
                        [
                            'complexType',
                            'simpleType',
                        ]
                    )
                ) {
                    $this->loadTypeWithCallback(
                        $element->getSchema(),
                        $childNode,
                        function (Type $type) use ($element) {
                            $element->setType($type);
                        }
                    );
                    $skip = true;
                }
            }
        );
        if ($skip) {
            return;
        }
        $this->fillItemNonLocalType($element, $node);
    }

    /**
     * @var Schema|null
     */
    protected $globalSchema;

    /**
     * @return Schema[]
     */
    protected function setupGlobalSchemas(array &$callbacks)
    {
        $globalSchemas = array();
        foreach (self::$globalSchemaInfo as $namespace => $uri) {
            self::setLoadedFile(
                $uri,
                $globalSchemas[$namespace] = $schema = new Schema()
            );
            if ($namespace === self::XSD_NS) {
                $this->globalSchema = $schema;
            }
            $xml = $this->getDOM($this->knownLocationSchemas[$uri]);
            $callbacks = array_merge($callbacks, $this->schemaNode($schema, $xml->documentElement));
        }

        return $globalSchemas;
    }

    /**
     * @return string[]
     */
    public function getGlobalSchemaInfo()
    {
        return self::$globalSchemaInfo;
    }

    /**
     * @return Schema
     */
    public function getGlobalSchema()
    {
        if (!$this->globalSchema) {
            $callbacks = array();
            $globalSchemas = $this->setupGlobalSchemas($callbacks);

            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], 'anySimpleType'));
            $globalSchemas[static::XSD_NS]->addType(new SimpleType($globalSchemas[static::XSD_NS], 'anyType'));

            $globalSchemas[static::XML_NS]->addSchema(
                $globalSchemas[static::XSD_NS],
                (string) static::XSD_NS
            );
            $globalSchemas[static::XSD_NS]->addSchema(
                $globalSchemas[static::XML_NS],
                (string) static::XML_NS
            );

            /**
             * @var Closure
             */
            foreach ($callbacks as $callback) {
                $callback();
            }
        }

        /**
         * @var Schema
         */
        $out = $this->globalSchema;

        return $out;
    }

    /**
     * @param DOMElement $node
     * @param string     $file
     *
     * @return Schema
     */
    public function readNode(DOMElement $node, $file = 'schema.xsd')
    {
        $fileKey = $node->hasAttribute('targetNamespace') ? $this->getNamespaceSpecificFileIndex($file, $node->getAttribute('targetNamespace')) : $file;
        self::setLoadedFile($fileKey, $rootSchema = new Schema());

        $rootSchema->addSchema($this->getGlobalSchema());
        $callbacks = $this->schemaNode($rootSchema, $node);

        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }

        return $rootSchema;
    }

    /**
     * It is possible that a single file contains multiple <xsd:schema/> nodes, for instance in a WSDL file.
     *
     * Each of these  <xsd:schema/> nodes typically target a specific namespace. Append the target namespace to the
     * file to distinguish between multiple schemas in a single file.
     *
     * @param string $file
     * @param string $targetNamespace
     *
     * @return string
     */
    public function getNamespaceSpecificFileIndex($file, $targetNamespace)
    {
        return $file.'#'.$targetNamespace;
    }

    /**
     * @param string $content
     * @param string $file
     *
     * @return Schema
     *
     * @throws IOException
     */
    public function readString($content, $file = 'schema.xsd')
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->loadXML($content)) {
            throw new IOException("Can't load the schema");
        }
        $xml->documentURI = $file;

        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     *
     * @return Schema
     */
    public function readFile($file)
    {
        $xml = $this->getDOM($file);

        return $this->readNode($xml->documentElement, $file);
    }

    /**
     * @param string $file
     *
     * @return DOMDocument
     *
     * @throws IOException
     */
    public function getDOM($file)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        if (!$xml->load($file)) {
            throw new IOException("Can't load the file $file");
        }

        return $xml;
    }

    public static function againstDOMNodeList(
        DOMElement $node,
        Closure $againstNodeList
    ) {
        $limit = $node->childNodes->length;
        for ($i = 0; $i < $limit; $i += 1) {
            /**
             * @var DOMNode
             */
            $childNode = $node->childNodes->item($i);

            if ($childNode instanceof DOMElement) {
                $againstNodeList(
                    $node,
                    $childNode
                );
            }
        }
    }

    public function maybeCallMethodAgainstDOMNodeList(
        DOMElement $node,
        SchemaItem $type,
        array $methods
    ) {
        static::againstDOMNodeList(
            $node,
            $this->CallbackGeneratorMaybeCallMethodAgainstDOMNodeList(
                $type,
                $methods
            )
        );
    }

    /**
     * @return Closure
     */
    public function CallbackGeneratorMaybeCallMethodAgainstDOMNodeList(
        SchemaItem $type,
        array $methods
    ) {
        return function (
            DOMElement $node,
            DOMElement $childNode
        ) use (
            $methods,
            $type
        ) {
            /**
             * @var string[]
             */
            $methods = $methods;

            $this->maybeCallMethod(
                $methods,
                $childNode->localName,
                $childNode,
                $type,
                $childNode
            );
        };
    }

    public function loadTypeWithCallbackOnChildNodes(
        Schema $schema,
        DOMElement $node,
        Closure $callback
    ) {
        self::againstDOMNodeList(
            $node,
            function (
                DOMElement $node,
                DOMElement $childNode
            ) use (
                $schema,
                $callback
            ) {
                $this->loadTypeWithCallback(
                    $schema,
                    $childNode,
                    $callback
                );
            }
        );
    }

    public function loadTypeWithCallback(
        Schema $schema,
        DOMElement $childNode,
        Closure $callback
    ) {
        $methods = [
            'complexType' => 'loadComplexType',
            'simpleType' => 'loadSimpleType',
        ];

        /**
         * @var Closure|null
         */
        $func = $this->maybeCallMethod(
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

    /**
     * @param string $file
     * @param string $namespace
     *
     * @return Closure
     */
    public function loadImport(
        Schema $schema,
        DOMElement $node
    ) {
        $base = urldecode($node->ownerDocument->documentURI);
        $file = UrlUtils::resolveRelativeUrl($base, $node->getAttribute('schemaLocation'));

        $namespace = $node->getAttribute('namespace');

        $keys = $this->loadImportFreshKeys($namespace, $file);

        if (
            self::hasLoadedFile(...$keys)
        ) {
            $schema->addSchema(self::getLoadedFile(...$keys));

            return function () {
            };
        }

        return $this->loadImportFresh($namespace, $schema, $file);
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return mixed[]
     */
    protected function loadImportFreshKeys(
        $namespace,
        $file
    ) {
        $globalSchemaInfo = $this->getGlobalSchemaInfo();

        $keys = [];

        if (isset($globalSchemaInfo[$namespace])) {
            $keys[] = $globalSchemaInfo[$namespace];
        }

        $keys[] = $this->getNamespaceSpecificFileIndex(
            $file,
            $namespace
        );

        $keys[] = $file;

        return $keys;
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Schema
     */
    protected function loadImportFreshCallbacksNewSchema(
        $namespace,
        Schema $schema,
        $file
    ) {
        /**
         * @var Schema $newSchema
         */
        $newSchema = self::setLoadedFile(
            $file,
            ($namespace ? new Schema() : $schema)
        );

        if ($namespace) {
            $newSchema->addSchema($this->getGlobalSchema());
            $schema->addSchema($newSchema);
        }

        return $newSchema;
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure[]
     */
    protected function loadImportFreshCallbacks(
        $namespace,
        Schema $schema,
        $file
    ) {
        /**
         * @var string
         */
        $file = $file;

        return $this->schemaNode(
            $this->loadImportFreshCallbacksNewSchema(
                $namespace,
                $schema,
                $file
            ),
            $this->getDOM(
                $this->hasKnownSchemaLocation($file)
                    ? $this->getKnownSchemaLocation($file)
                    : $file
            )->documentElement,
            $schema
        );
    }

    /**
     * @param string $namespace
     * @param string $file
     *
     * @return Closure
     */
    protected function loadImportFresh(
        $namespace,
        Schema $schema,
        $file
    ) {
        return function () use ($namespace, $schema, $file) {
            foreach (
                $this->loadImportFreshCallbacks(
                    $namespace,
                    $schema,
                    $file
                ) as $callback
            ) {
                $callback();
            }
        };
    }

    /**
     * @return Element
     */
    public function loadElement(
        Schema $schema,
        DOMElement $node
    ) {
        $element = new Element($schema, $node->getAttribute('name'));
        $element->setDoc(self::getDocumentation($node));

        $this->fillItem($element, $node);

        self::maybeSetMax($element, $node);
        self::maybeSetMin($element, $node);

        $xp = new \DOMXPath($node->ownerDocument);
        $xp->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        if ($xp->query('ancestor::xs:choice', $node)->length) {
            $element->setMin(0);
        }

        if ($node->hasAttribute('nillable')) {
            $element->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $element->setQualified($node->getAttribute('form') == 'qualified');
        }

        return $element;
    }

    /**
     * @return ElementRef
     */
    public static function loadElementRef(
        ElementDef $referenced,
        DOMElement $node
    ) {
        $ref = new ElementRef($referenced);
        $ref->setDoc(self::getDocumentation($node));

        self::maybeSetMax($ref, $node);
        self::maybeSetMin($ref, $node);
        if ($node->hasAttribute('nillable')) {
            $ref->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $ref->setQualified($node->getAttribute('form') == 'qualified');
        }

        return $ref;
    }

    /**
     * @return \Closure
     */
    public function loadAttributeGroup(
        Schema $schema,
        DOMElement $node
    ) {
        $attGroup = new AttributeGroup($schema, $node->getAttribute('name'));
        $attGroup->setDoc(self::getDocumentation($node));
        $schema->addAttributeGroup($attGroup);

        return function () use ($schema, $node, $attGroup) {
            SchemaReader::againstDOMNodeList(
                $node,
                function (
                    DOMElement $node,
                    DOMElement $childNode
                ) use (
                    $schema,
                    $attGroup
                ) {
                    switch ($childNode->localName) {
                        case 'attribute':
                            $attribute = $this->getAttributeFromAttributeOrRef(
                                $childNode,
                                $schema,
                                $node
                            );
                            $attGroup->addAttribute($attribute);
                            break;
                        case 'attributeGroup':
                            $this->findSomethingLikeAttributeGroup(
                                $schema,
                                $node,
                                $childNode,
                                $attGroup
                            );
                            break;
                    }
                }
            );
        };
    }

    /**
     * @return AttributeItem
     */
    public function getAttributeFromAttributeOrRef(
        DOMElement $childNode,
        Schema $schema,
        DOMElement $node
    ) {
        if ($childNode->hasAttribute('ref')) {
            /**
             * @var AttributeItem
             */
            $attribute = $this->findSomething('findAttribute', $schema, $node, $childNode->getAttribute('ref'));
        } else {
            /**
             * @var Attribute
             */
            $attribute = $this->loadAttribute($schema, $childNode);
        }

        return $attribute;
    }

    /**
     * @return Attribute
     */
    public function loadAttribute(
        Schema $schema,
        DOMElement $node
    ) {
        $attribute = new Attribute($schema, $node->getAttribute('name'));
        $attribute->setDoc(self::getDocumentation($node));
        $this->fillItem($attribute, $node);

        if ($node->hasAttribute('nillable')) {
            $attribute->setNil($node->getAttribute('nillable') == 'true');
        }
        if ($node->hasAttribute('form')) {
            $attribute->setQualified($node->getAttribute('form') == 'qualified');
        }
        if ($node->hasAttribute('use')) {
            $attribute->setUse($node->getAttribute('use'));
        }

        return $attribute;
    }

    public function addAttributeFromAttributeOrRef(
        BaseComplexType $type,
        DOMElement $childNode,
        Schema $schema,
        DOMElement $node
    ) {
        $attribute = $this->getAttributeFromAttributeOrRef(
            $childNode,
            $schema,
            $node
        );

        $type->addAttribute($attribute);
    }

    public function findSomethingLikeAttributeGroup(
        Schema $schema,
        DOMElement $node,
        DOMElement $childNode,
        AttributeContainer $addToThis
    ) {
        /**
         * @var AttributeItem
         */
        $attribute = $this->findSomething('findAttributeGroup', $schema, $node, $childNode->getAttribute('ref'));
        $addToThis->addAttribute($attribute);
    }

    /**
     * @var Schema[]
     */
    protected static $loadedFiles = array();

    /**
     * @param string ...$keys
     *
     * @return bool
     */
    public static function hasLoadedFile(...$keys)
    {
        foreach ($keys as $key) {
            if (isset(self::$loadedFiles[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string ...$keys
     *
     * @return Schema
     *
     * @throws RuntimeException if loaded file not found
     */
    public static function getLoadedFile(...$keys)
    {
        foreach ($keys as $key) {
            if (isset(self::$loadedFiles[$key])) {
                return self::$loadedFiles[$key];
            }
        }

        throw new RuntimeException('Loaded file was not found!');
    }

    /**
     * @param string $key
     *
     * @return Schema
     */
    public static function setLoadedFile($key, Schema $schema)
    {
        self::$loadedFiles[$key] = $schema;

        return $schema;
    }
}
