<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Jackalope\Session;
use Rabble\ContentBundle\Content\Transformer\ContentTransformerInterface;
use Rabble\ContentBundle\Content\Translator\ContentTranslatorInterface;
use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\Exception\InvalidContentDocumentException;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class ContentDocumentHydrator implements LocaleAwareDocumentHydratorInterface
{
    private Session $session;
    private ContentTranslatorInterface $contentTranslator;
    private ReflectionHydrator $baseHydrator;
    private DocumentFieldsProviderInterface $fieldsProvider;
    private string $locale;

    public function __construct(
        Session $session,
        ContentTranslatorInterface $contentTranslator,
        ReflectionHydrator $baseHydrator,
        DocumentFieldsProviderInterface $fieldsProvider,
        string $defaultLocale
    ) {
        $this->session = $session;
        $this->contentTranslator = $contentTranslator;
        $this->baseHydrator = $baseHydrator;
        $this->fieldsProvider = $fieldsProvider;
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

        $object = new \ReflectionObject($document);
        $property = $object->getProperty('dirty');
        $property->setAccessible(true);
        $property->setValue($document, false);
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        $this->baseHydrator->hydrateNode($document, $node);
        $this->contentTranslator->setNodeData($document, $this->locale);
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
