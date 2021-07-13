<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class ChainedDocumentHydrator implements LocaleAwareDocumentHydratorInterface
{
    private AnnotationHydrator $annotationHydrator;
    /** @var DocumentHydratorInterface[] */
    private array $hydrators;

    private string $locale;

    public function __construct(AnnotationHydrator $annotationHydrator, array $hydrators = [])
    {
        $this->annotationHydrator = $annotationHydrator;
        $this->hydrators = $hydrators;
    }

    public function addHydrator(DocumentHydratorInterface $hydrator): void
    {
        $this->hydrators[] = $hydrator;
    }

    public function setHydrators(array $hydrators): void
    {
        $this->hydrators = $hydrators;
    }

    public function hydrateDocument(AbstractPersistenceDocument $document, Node $node): void
    {
        $this->annotationHydrator->hydrateDocument($document, $node);
        foreach ($this->hydrators as $hydrator) {
            $hydrator->hydrateDocument($document, $node);
        }
        $object = new \ReflectionObject($document);

        $property = $object->getProperty('dirty');
        $property->setAccessible(true);
        $property->setValue($document, false);
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        $this->annotationHydrator->hydrateNode($document, $node);
        foreach ($this->hydrators as $hydrator) {
            $hydrator->hydrateNode($document, $node);
        }
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
        foreach ($this->hydrators as $hydrator) {
            if ($hydrator instanceof LocaleAwareDocumentHydratorInterface) {
                $hydrator->setLocale($locale);
            }
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
