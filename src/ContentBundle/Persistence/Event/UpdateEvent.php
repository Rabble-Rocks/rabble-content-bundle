<?php

namespace Rabble\ContentBundle\Persistence\Event;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class UpdateEvent extends OperationEvent
{
    private array $oldProperties;
    private array $newProperties;

    public function __construct(AbstractPersistenceDocument $document, array $oldProperties, array $newProperties)
    {
        parent::__construct($document);
        $this->oldProperties = $oldProperties;
        $this->newProperties = $newProperties;
    }

    public function getOldProperties(): array
    {
        return $this->oldProperties;
    }

    public function getNewProperties(): array
    {
        return $this->newProperties;
    }
}
