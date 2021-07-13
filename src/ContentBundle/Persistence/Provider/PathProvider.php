<?php

namespace Rabble\ContentBundle\Persistence\Provider;

use Jackalope\Session;
use PHPCR\RepositoryException;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Provider\NodeName\NodeNameProviderInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class PathProvider implements PathProviderInterface
{
    private NodeNameProviderInterface $nodeNameProvider;
    private SluggerInterface $slugger;
    private Session $session;

    public function __construct(
        NodeNameProviderInterface $nodeNameProvider,
        SluggerInterface $slugger,
        Session $session
    ) {
        $this->nodeNameProvider = $nodeNameProvider;
        $this->slugger = $slugger;
        $this->session = $session;
    }

    public function provide(AbstractPersistenceDocument $document): string
    {
        $rootNode = $document::ROOT_NODE;
        if ('/' === $rootNode) {
            $rootNode = '';
        }
        $nodeName = strtolower($this->slugger->slug($this->nodeNameProvider->provide($document), '-'));
        $suffix = '';
        for ($i = 1; $this->hasCollision($path = sprintf('%s/%s', $rootNode, $nodeName.$suffix)); ++$i) {
            $suffix = "-{$i}";
        }

        return $path;
    }

    private function hasCollision($path): bool
    {
        try {
            $this->session->getNode($path);
        } catch (RepositoryException $exception) {
            return false;
        }

        return true;
    }
}
