<?php

namespace Rabble\ContentBundle\Persistence\Provider\NodeName;

use Doctrine\Common\Annotations\Reader;
use Rabble\ContentBundle\Annotation\NodeName;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class NodeNameProvider implements NodeNameProviderInterface
{
    private Reader $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function provide(AbstractPersistenceDocument $document): string
    {
        $object = new \ReflectionObject($document);

        foreach ($object->getProperties() as $property) {
            $nodeName = $this->annotationReader->getPropertyAnnotation($property, NodeName::class);
            if ($nodeName instanceof NodeName) {
                $getter = 'get'.ucfirst($property->getName());
                if (method_exists($document, $getter)) {
                    $value = $document->{$getter}();
                } else {
                    $property->setAccessible(true);
                    $value = $property->getValue($document);
                }

                return str_replace('/', '%2F', $value);
            }
        }

        return $document->getNodeName();
    }
}
