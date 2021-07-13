<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface DocumentHydratorInterface
{
    public function hydrateDocument(AbstractPersistenceDocument $document, Node $node): void;

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void;
}
