<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Jackalope\Session;
use Rabble\ContentBundle\Content\Transformer\ContentTransformerInterface;
use Rabble\ContentBundle\Content\Translator\ContentTranslatorInterface;
use Rabble\ContentBundle\Exception\InvalidContentDocumentException;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class ContentDocumentHydrator implements LocaleAwareDocumentHydratorInterface
{
    private Session $session;
    private ContentTranslatorInterface $contentTranslator;
    private ContentTransformerInterface $contentTransformer;
    private ReflectionHydrator $baseHydrator;
    private string $locale;

    public function __construct(
        Session $session,
        ContentTranslatorInterface $contentTranslator,
        ContentTransformerInterface $contentTransformer,
        ReflectionHydrator $baseHydrator,
        string $defaultLocale
    ) {
        $this->session = $session;
        $this->contentTranslator = $contentTranslator;
        $this->contentTransformer = $contentTransformer;
        $this->baseHydrator = $baseHydrator;
        $this->locale = $defaultLocale;
    }

    public function hydrateDocument(AbstractPersistenceDocument $document, ?Node $node = null): void
    {
        $node = $node ?? $this->session->getObjectManager()->getNodeByPath($document->getPath());
        if (!$node instanceof Node) {
            throw new InvalidContentDocumentException();
        }
        $this->baseHydrator->hydrateDocument($document, $node);
        if (Node::STATE_NEW !== $node->getState()) {
            $this->contentTranslator->translate($document, $this->locale);
        }
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        $this->baseHydrator->hydrateNode($document, $node);
        $data = $this->contentTranslator->localizeNodeData($document, $this->locale);
        $this->contentTransformer->setData($node, $data);
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
