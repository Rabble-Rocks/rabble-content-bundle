<?php

namespace Rabble\ContentBundle\Persistence\Event;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Contracts\EventDispatcher\Event;

class PostFindEvent extends Event
{
    private AbstractPersistenceDocument $document;

    public function __construct(AbstractPersistenceDocument $document)
    {
        $this->document = $document;
    }

    public function getDocument(): AbstractPersistenceDocument
    {
        return $this->document;
    }
}
