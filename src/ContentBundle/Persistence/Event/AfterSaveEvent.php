<?php

namespace Rabble\ContentBundle\Persistence\Event;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Contracts\EventDispatcher\Event;

class AfterSaveEvent extends Event
{
    /** @var AbstractPersistenceDocument[] */
    private array $updated;
    /** @var AbstractPersistenceDocument[] */
    private array $inserted;
    /** @var AbstractPersistenceDocument[] */
    private array $removed;

    public function __construct(array $updated, array $inserted, array $removed)
    {
        $this->updated = $updated;
        $this->inserted = $inserted;
        $this->removed = $removed;
    }

    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * @return AbstractPersistenceDocument[]
     */
    public function getInserted(): array
    {
        return $this->inserted;
    }

    /**
     * @return AbstractPersistenceDocument[]
     */
    public function getRemoved(): array
    {
        return $this->removed;
    }
}
