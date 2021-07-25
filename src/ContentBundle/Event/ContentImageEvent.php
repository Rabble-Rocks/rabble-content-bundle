<?php

namespace Rabble\ContentBundle\Event;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Mapping\PropertyMapping;

class ContentImageEvent extends Event
{
    private AbstractPersistenceDocument $document;

    public function __construct($object, PropertyMapping $mapping, AbstractPersistenceDocument $document)
    {
        parent::__construct($object, $mapping);
        $this->document = $document;
    }

    public function getDocument(): AbstractPersistenceDocument
    {
        return $this->document;
    }
}
