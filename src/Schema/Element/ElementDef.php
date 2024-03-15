<?php

declare(strict_types=1);

namespace GoetasWebservices\XML\XSDReader\Schema\Element;

/**
 * An ElementDef represents an element definition in the root context of a schema.
 * It can be referenced by an ElementRef.
 */
class ElementDef extends AbstractElementSingle
{
    /**
     * @var AbstractElementSingle[]
     */
    private array $substitutionCandidates = [];

    public function hasSubstitutionCandidates(): bool
    {
        return 0 < count($this->getSubstitutionCandidates());
    }

    /**
     * @return AbstractElementSingle[]
     */
    public function getSubstitutionCandidates(): array
    {
        return $this->substitutionCandidates;
    }

    /**
     * @param AbstractElementSingle[] $substitutionCandidates
     */
    public function setSubstitutionCandidates(array $substitutionCandidates): void
    {
        $this->substitutionCandidates = $substitutionCandidates;
    }

    public function addSubstitutionCandidate(AbstractElementSingle $substitutionCandidate): void
    {
        $this->substitutionCandidates[] = $substitutionCandidate;
    }
}
