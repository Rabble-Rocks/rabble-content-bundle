<?php

namespace Rabble\ContentBundle\Persistence\Provider;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;

class StructuredContentPathProvider extends PathProvider
{
    public function provide(AbstractPersistenceDocument $document): string
    {
        if (!$document instanceof StructuredDocumentInterface) {
            throw new \InvalidArgumentException('Expecting StructuredDocumentInterface. Got: '.get_class($document));
        }
        if (null !== $document->getParent()) {
            $parent = $this->session->getNodeByIdentifier($document->getParent()->getUuid());

            return $this->doProvide($document, $parent->getPath());
        }

        return $document::ROOT_NODE;
    }

    public function supports(AbstractPersistenceDocument $document): bool
    {
        return $document instanceof StructuredDocumentInterface && (null !== $document->getParent() || StructuredDocument::ROOT_NODE === $document::ROOT_NODE);
    }
}
