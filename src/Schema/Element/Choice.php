<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

use GoetasWebservices\XML\XSDReader\Schema\AbstractNamedGroupItem;

class Choice extends AbstractNamedGroupItem implements ElementItem, ElementContainer, InterfaceSetMinMax
{
    use ElementContainerTrait;
    use MinMaxTrait;

    /**
     * A choice selects exactly one of its members per occurrence. When the
     * choice itself is repeatable, each member can therefore occur any number
     * of times across the repetitions. Multiplying the occurrences (as is done
     * for a group reference around a sequence) is unsound here, so repeatable
     * members are exposed as unbounded lists (maxOccurs unbounded).
     *
     * For minOccurs: when the choice has more than one member, every member is
     * optional (another branch may be selected instead), so minOccurs becomes
     * 0. A single-member choice degenerates to that one member, so it keeps the
     * choice's own minOccurs.
     *
     * @return ElementItem[]
     */
    public function getElements(): array
    {
        $max = $this->getMax();
        if (-1 !== $max && $max <= 1) {
            return $this->elements;
        }

        $min = count($this->elements) > 1 ? 0 : $this->getMin();

        $elements = $this->elements;
        foreach ($elements as $k => $element) {
            if (!$element instanceof InterfaceSetMinMax) {
                continue;
            }

            $clonedElement = clone $element;
            $clonedElement->setMin($min);
            $clonedElement->setMax(-1);
            $elements[$k] = $clonedElement;
        }

        return $elements;
    }
}
