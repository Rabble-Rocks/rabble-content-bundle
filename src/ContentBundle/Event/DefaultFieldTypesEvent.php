<?php

namespace Rabble\ContentBundle\Event;

use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class DefaultFieldTypesEvent extends Event
{
    /** @var FieldTypeInterface[] */
    private array $fieldTypes = [];

    public function addFieldType(FieldTypeInterface $fieldType): void
    {
        $this->fieldTypes[] = $fieldType;
    }

    public function addFieldTypes(array $fieldTypes): void
    {
        foreach ($fieldTypes as $fieldType) {
            $this->addFieldType($fieldType);
        }
    }

    /**
     * @return FieldTypeInterface[]
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }
}
