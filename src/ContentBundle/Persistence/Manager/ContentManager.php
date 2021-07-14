<?php

namespace Rabble\ContentBundle\Persistence\Manager;

use Jackalope\Factory;
use Jackalope\Node;
use Jackalope\Session;
use PHPCR\RepositoryException;
use PHPCR\Util\PathHelper;
use PHPCR\Util\UUIDHelper;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Event\AfterSaveEvent;
use Rabble\ContentBundle\Persistence\Event\InsertEvent;
use Rabble\ContentBundle\Persistence\Event\PostFindEvent;
use Rabble\ContentBundle\Persistence\Event\RemoveEvent;
use Rabble\ContentBundle\Persistence\Event\UpdateEvent;
use Rabble\ContentBundle\Persistence\Hydrator\DocumentHydratorInterface;
use Rabble\ContentBundle\Persistence\Provider\PathProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ContentManager implements ContentManagerInterface
{
    private Session $session;
    private Factory $factory;
    private EventDispatcherInterface $eventDispatcher;
    /** @var AbstractPersistenceDocument[] */
    private array $documents = [];
    private array $ids = [];

    /** @var AbstractPersistenceDocument[] */
    private array $scheduledForRemoval = [];
    /** @var AbstractPersistenceDocument[] */
    private array $scheduledForInsertion = [];

    private string $locale;
    private DocumentHydratorInterface $documentHydrator;
    private PathProviderInterface $pathProvider;
    private Configuration $proxyConfiguration;

    public function __construct(
        Session $session,
        EventDispatcherInterface $eventDispatcher,
        PathProviderInterface $pathProvider,
        DocumentHydratorInterface $documentHydrator,
        Configuration $proxyConfiguration,
        string $defaultLocale
    ) {
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
        $this->factory = new Factory();
        $this->documentHydrator = $documentHydrator;
        $this->pathProvider = $pathProvider;
        $this->proxyConfiguration = $proxyConfiguration;
        $this->locale = $defaultLocale;
    }

    public function find(string $id): ?AbstractPersistenceDocument
    {
        try {
            if (UUIDHelper::isUUID($id)) {
                $node = $this->session->getNodeByIdentifier($id);
            } else {
                $node = $this->session->getItem($id);
            }
        } catch (RepositoryException $exception) {
            return null;
        }
        if (!$node instanceof Node || !$node->hasProperty('rabble:class')) {
            return null;
        }
        $uuid = $node->getPropertyValue('jcr:uuid');
        if (isset($this->ids[$uuid], $this->documents[$this->ids[$uuid]])) {
            return $this->documents[$this->ids[$uuid]];
        }
        $document = $this->createProxy($node->getPropertyValue('rabble:class'), $node);
        $this->addToIndex($document);
        $this->ids[$uuid] = spl_object_hash($document);
        $this->eventDispatcher->dispatch(new PostFindEvent($document), 'rabble_content.post_find');

        return $document;
    }

    public function contains(AbstractPersistenceDocument $document): bool
    {
        $objectHash = spl_object_hash($document);

        return isset($this->documents[$objectHash]);
    }

    public function persist(AbstractPersistenceDocument $document): void
    {
        if (!$this->contains($document)) {
            $this->addToIndex($document);
            $objectHash = spl_object_hash($document);
            if (isset($this->scheduledForRemoval[$objectHash])) {
                unset($this->scheduledForRemoval[$objectHash]);
            } else {
                $this->scheduledForInsertion[$objectHash] = $document;
            }
        }
    }

    public function remove(AbstractPersistenceDocument $document): void
    {
        $this->removeFromIndex($document);
        $this->scheduledForRemoval[spl_object_hash($document)] = $document;
    }

    public function flush(): void
    {
        $updated = [];
        $inserted = [];
        $removed = [];
        foreach (array_merge($this->documents, $this->scheduledForRemoval) as $objectHash => $document) {
            if (isset($this->scheduledForInsertion[$objectHash])) {
                $this->eventDispatcher->dispatch($event = new InsertEvent($document));
                if (!$event->isPrevented()) {
                    $path = $this->pathProvider->provide($document);

                    try {
                        $existingNode = $this->session->getItem($path);
                    } catch (RepositoryException $exception) {
                        $existingNode = null;
                    }
                    if (null === $existingNode) {
                        $node = $this->addNode($path);
                        $document->setDocumentClass(get_class($document));
                        $document->setDefaultLocale($this->locale);
                        $this->documentHydrator->hydrateDocument($document, $node);
                        $this->documentHydrator->hydrateNode($document, $node);
                        $inserted[] = $document;
                    }
                }

                continue;
            }
            if (isset($this->scheduledForRemoval[$objectHash])) {
                $this->eventDispatcher->dispatch($event = new RemoveEvent($document));
                if (!$event->isPrevented()) {
                    $this->session->removeItem($document->getPath());
                    $removed[] = $document;

                    continue;
                }
                $this->addToIndex($document);

                continue;
            }
            if ($document->isDirty()) {
                $node = $this->session->getItem($document->getPath());
                $oldDocument = $this->createProxy($node->getPropertyValue('rabble:class'), $node);
                $this->eventDispatcher->dispatch($event = new UpdateEvent($document, $oldDocument->getProperties(), $document->getProperties()));
                if ($event->isPrevented()) {
                    $this->refresh($document);

                    continue;
                }
                $this->documentHydrator->hydrateNode($document, $node);
                $parentPath = PathHelper::getParentPath($this->pathProvider->provide($document));
                $path = ('/' === $parentPath ? '' : $parentPath).'/'.$document->getNodeName();
                if ($path !== $document->getPath()) {
                    try {
                        $this->move($document, $path);
                    } catch (RepositoryException $exception) {
                    }
                }
                $updated[] = $document;
            }
        }
        $this->scheduledForInsertion = [];
        $this->scheduledForRemoval = [];
        if (count($updated) > 0 || count($inserted) > 0 || count($removed) > 0) {
            $this->session->save();
            $this->session->refresh(false);
            foreach ($inserted as $item) {
                $this->refresh($item);
            }
            $this->eventDispatcher->dispatch(new AfterSaveEvent($updated, $inserted, $removed));
        }
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        if ($this->documentHydrator instanceof LocaleAwareInterface) {
            $this->documentHydrator->setLocale($locale);
        }
        $this->locale = $locale;
    }

    public function refresh(AbstractPersistenceDocument $document): void
    {
        $node = $this->session->getItem($document->getPath());
        $this->documentHydrator->hydrateDocument($document, $node);
    }

    public function move(AbstractPersistenceDocument $document, $targetPath): void
    {
        $parent = $this->session->getItem(PathHelper::getParentPath($targetPath));
        $suffix = '';
        $i = 1;
        while (
            $parent->hasNode(PathHelper::getNodeName($targetPath.$suffix))
            || $parent->hasProperty(PathHelper::getNodeName($targetPath.$suffix))
        ) {
            $suffix = "-{$i}";
            ++$i;
        }
        $this->session->move($document->getPath(), $targetPath.$suffix);
        $document->setNodeName(PathHelper::getNodeName($targetPath.$suffix));
        $document->setPath($targetPath.$suffix);
    }

    protected function addNode(string $path): Node
    {
        $current = $this->session->getRootNode();
        $segments = preg_split('#/#', $path, null, PREG_SPLIT_NO_EMPTY);
        foreach ($segments as $segment) {
            if ($current->hasNode($segment)) {
                $current = $current->getNode($segment);
            } else {
                $currentPath = $current->getPath().('/' === $current->getPath() ? '' : '/').$segment;
                $current = new Node($this->factory, [
                    'jcr:primaryType' => 'nt:unstructured',
                    'jcr:mixinTypes' => ['mix:referenceable'],
                ], $currentPath, $this->session, $this->session->getObjectManager(), true);
                $this->session->getObjectManager()->addNode($currentPath, $current);
            }
        }

        return $current;
    }

    private function createProxy(string $documentClass, Node $node): AbstractPersistenceDocument
    {
        $factory = new LazyLoadingGhostFactory($this->proxyConfiguration);

        return $factory->createProxy($documentClass, function (
            GhostObjectInterface $ghostObject,
            string $method,
            array $parameters,
            &$initializer
        ) use ($node) {
            $initializer = null;
            $this->documentHydrator->hydrateDocument($ghostObject, $node);

            return true;
        });
    }

    private function addToIndex(AbstractPersistenceDocument $document): void
    {
        $this->documents[spl_object_hash($document)] = $document;
    }

    private function removeFromIndex(AbstractPersistenceDocument $document): void
    {
        if (isset($this->documents[spl_object_hash($document)])) {
            unset($this->documents[spl_object_hash($document)]);
        }
    }
}
