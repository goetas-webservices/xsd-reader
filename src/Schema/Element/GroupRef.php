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

    /**
     * @return $this
     */
    public function setMin(int $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function getMax(): int
    {
        return $this->max;
    }

    /**
     * @return $this
     */
    public function setMax(int $max): self
    {
        $this->max = $max;

        return $this;
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
        if ($this->getMax() > 0 || $this->getMax() === -1) {
            /**
             * @var string $k
             */
            foreach ($elements as $k => $element) {
                /**
                 * @var Element|ElementRef|ElementSingle|GroupRef $e
                 */
                $e = clone $element;
                $e->setMax($this->getMax());
                $elements[$k] = $e;
            }
        }

        return $elements;
    }

    public function addElement(ElementItem $element)
    {
        throw new BadMethodCallException("Can't add an element for a ref group");
    }
}
