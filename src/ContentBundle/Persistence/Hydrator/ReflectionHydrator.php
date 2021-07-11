<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Doctrine\Common\Annotations\Reader;
use Jackalope\Node;
use Rabble\ContentBundle\Annotation\NodeProperty;
use Rabble\ContentBundle\Exception\InvalidContentDocumentException;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Provider\NodeName\NodeNameProviderInterface;

class ReflectionHydrator implements DocumentHydratorInterface
{
    private const ILLEGAL_SET_PROPERTIES = ['jcr:uuid', 'path'];

    private Reader $annotationReader;
    private NodeNameProviderInterface $nodeNameProvider;

    public function __construct(
        Reader $annotationReader,
        NodeNameProviderInterface $nodeNameProvider
    ) {
        $this->annotationReader = $annotationReader;
        $this->nodeNameProvider = $nodeNameProvider;
    }

    public function hydrateDocument(AbstractPersistenceDocument $document, ?Node $node = null): void
    {
        if (null === $node) {
            throw new InvalidContentDocumentException();
        }
        $object = new \ReflectionObject($document);
        foreach ($object->getProperties() as $property) {
            $nodeProperty = $this->annotationReader->getPropertyAnnotation($property, NodeProperty::class);
            if (!$nodeProperty instanceof NodeProperty) {
                continue;
            }
            $value = $nodeProperty->getValue($node);
            if (null !== $value) {
                $property->setAccessible(true);
                $property->setValue($document, $value);
            }
        }
        $nodeNameProperty = $object->getProperty('nodeName');
        $nodeNameProperty->setAccessible(true);
        if (Node::STATE_NEW === $node->getState() && null === $document->getNodeName() ?? null) {
            $nodeName = $this->nodeNameProvider->provide($document);
        } else {
            $nodeName = $node->getName();
        }
        $nodeNameProperty->setValue($document, $nodeName);

        $property = $object->getProperty('dirty');
        $property->setAccessible(true);
        $property->setValue($document, false);
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        if (null === $node) {
            throw new InvalidContentDocumentException();
        }
        $object = new \ReflectionObject($document);
        foreach ($object->getProperties() as $property) {
            $nodeProperty = $this->annotationReader->getPropertyAnnotation($property, NodeProperty::class);
            if (!$nodeProperty instanceof NodeProperty || in_array($nodeProperty->name, self::ILLEGAL_SET_PROPERTIES)) {
                continue;
            }
            $property->setAccessible(true);
            $nodeProperty->setValue($node, $property->getValue($document));
        }
    }
}
