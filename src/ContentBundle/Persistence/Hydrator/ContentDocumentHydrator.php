<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Rabble\ContentBundle\Content\Translator\ContentTranslatorInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class ContentDocumentHydrator implements LocaleAwareDocumentHydratorInterface
{
    private ContentTranslatorInterface $contentTranslator;
    private string $locale;

    public function __construct(
        ContentTranslatorInterface $contentTranslator,
        string $defaultLocale
    ) {
        $this->contentTranslator = $contentTranslator;
        $this->locale = $defaultLocale;
    }

    public function hydrateDocument(AbstractPersistenceDocument $document, Node $node): void
    {
        if (Node::STATE_NEW !== $node->getState()) {
            $this->contentTranslator->translate($document, $this->locale, $node);
        }
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        $this->contentTranslator->setNodeData($document, $this->locale, $node);
    }

    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
