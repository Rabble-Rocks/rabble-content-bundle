<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface DocumentHydratorInterface
{
    /**
     * If no node is given, the hydrator should try to find the node from the PHPCR session.
     */
    public function hydrateDocument(AbstractPersistenceDocument $document, ?Node $node = null): void;

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void;
}
