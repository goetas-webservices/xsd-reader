<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use BadMethodCallException;

class GroupRef extends Group implements InterfaceSetMinMax
{
    /**
     * @var Group
     */
    protected $wrapped;

    /**
     * @var int
     */
    protected $min = 1;

    /**
     * @var int
     */
    protected $max = 1;

    public function __construct(Group $group)
    {
        parent::__construct($group->getSchema(), '');
        $this->wrapped = $group;
    }

    public function getMin(): int
    {
        return $this->min;
    }

    public function setMin(int $min): void
    {
        $this->min = $min;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    public function setMax(int $max): void
    {
        $this->max = $max;
    }

    public function getName(): string
    {
        return $this->wrapped->getName();
    }

    /**
     * @return ElementItem[]
     */
    public function getElements(): array
    {
        $elements = $this->wrapped->getElements();

        /**
         * @var int $k
         */
        foreach ($elements as $k => $element) {
            /**
             * @var Element|ElementRef|ElementSingle|GroupRef $e
             */
            $e = clone $element;
            if ($this->getMax() > 0 || $this->getMax() === -1) {
                $e->setMax($this->getMax());
            }

            if ($this->getMin() > 1 && $e->getMin() === 1) {
                $e->setMin($this->getMin());
            }

            $elements[$k] = $e;
        }

        return $elements;
    }

    public function addElement(ElementItem $element): void
    {
        throw new BadMethodCallException("Can't add an element for a ref group");
    }
}
