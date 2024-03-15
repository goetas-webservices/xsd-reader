<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

class GroupRef extends Group implements InterfaceSetMinMax
{
    protected Group $wrapped;

    protected int $min = 1;

    protected int $max = 1;

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
             * @var Element|ElementRef|ElementSingle|GroupRef $clonedElement
             */
            $clonedElement = clone $element;
            if (0 < $this->getMax() || -1 === $this->getMax()) {
                $clonedElement->setMax($this->getMax());
            }

            if (1 < $this->getMin() && 1 === $clonedElement->getMin()) {
                $clonedElement->setMin($this->getMin());
            }

            $elements[$k] = $clonedElement;
        }

        return $elements;
    }

    public function addElement(ElementItem $element): void
    {
        throw new \BadMethodCallException("Can't add an element for a ref group");
    }
}
