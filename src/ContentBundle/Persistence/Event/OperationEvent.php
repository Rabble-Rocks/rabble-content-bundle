<?php

namespace Rabble\ContentBundle\Persistence\Event;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Contracts\EventDispatcher\Event;

class OperationEvent extends Event
{
    private AbstractPersistenceDocument $document;
    private bool $isPrevented = false;

    public function __construct(AbstractPersistenceDocument $document)
    {
        $this->document = $document;
    }

    public function getDocument(): AbstractPersistenceDocument
    {
        return $this->document;
    }

    public function prevent(bool $prevent = true): void
    {
        $this->isPrevented = $prevent;
    }

    public function isPrevented(): bool
    {
        return $this->isPrevented;
    }
}
