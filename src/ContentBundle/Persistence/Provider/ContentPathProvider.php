<?php

namespace Rabble\ContentBundle\Persistence\Provider;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Provider\NodeName\NodeNameProviderInterface;

class ContentPathProvider implements PathProviderInterface
{
    public const ROOT_NODE = '/content';

    private NodeNameProviderInterface $nodeNameProvider;

    public function __construct(NodeNameProviderInterface $nodeNameProvider)
    {
        $this->nodeNameProvider = $nodeNameProvider;
    }

    public function provide(AbstractPersistenceDocument $document): string
    {
        if (!$document instanceof ContentDocument) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of ContentDocument. Got: %s', get_class($document)));
        }

        return sprintf('%s/%s/%s', self::ROOT_NODE, $document->getContentType(), $this->nodeNameProvider->provide($document));
    }
}
