<?php

namespace Rabble\ContentBundle\Content\EventListener;

use PHPCR\Util\PathHelper;
use Rabble\ContentBundle\Content\ContentIndexer;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Event\AfterSaveEvent;
use Rabble\ContentBundle\Persistence\Event\UpdateEvent;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentIndexSubscriber implements EventSubscriberInterface
{
    /** @var ContentIndexer[]|iterable */
    private iterable $indexers;
    private ContentManager $contentManager;

    public function __construct(iterable $indexers, ContentManager $contentManager)
    {
        $this->indexers = $indexers;
        $this->contentManager = $contentManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterSaveEvent::class => ['afterSave', -128],
            UpdateEvent::class => ['onUpdate', -128],
        ];
    }

    public function onUpdate(UpdateEvent $event): void
    {
        $old = $event->getOldProperties();
        $new = $event->getNewProperties();
        $document = $event->getDocument();
        if (!$document instanceof StructuredDocumentInterface) {
            return;
        }
        if (isset($old['slug'], $new['slug']) && $old['slug'] !== $new['slug']) {
            $this->slugUpdated($document, $old['slug'], $new['slug']);
        }
        $oldParent = $old['parent'] ?? null;
        if (null === $oldParent || null === $document->getParent()) {
            return;
        }
        if (0 === strpos($document->getProperty('slug'), $oldParent->getProperty('slug'))) {
            $newSlug = rtrim($document->getParent()->getProperty('slug'), '/').'/'.trim(substr($document->getProperty('slug'), strlen($oldParent->getProperty('slug'))), '/');
            $oldSlug = $document->getProperty('slug');
            $document->setProperty('slug', $newSlug);
            $this->slugUpdated($document, $oldSlug, $newSlug);
        }
    }

    public function afterSave(AfterSaveEvent $event): void
    {
        foreach ($this->indexers as $indexer) {
            $indexed = false;
            foreach ($event->getInserted() as $document) {
                if ($indexer->supports($document)) {
                    $indexer->index($document);
                    $indexed = true;
                }
            }
            foreach ($event->getUpdated() as $document) {
                if ($indexer->supports($document)) {
                    $indexer->update($document);
                    $indexed = true;
                }
            }
            foreach ($event->getRemoved() as $document) {
                if ($indexer->supports($document)) {
                    $indexer->remove($document);
                    $indexed = true;
                }
            }
            if (!$indexed) {
                continue;
            }
            if (0 < count($event->getInserted()) + count($event->getRemoved())) {
                $indexer->commitAll();

                continue;
            }
            $indexer->commit(false);
        }
    }

    private function slugUpdated(AbstractPersistenceDocument $document, $oldSlug, $newSlug)
    {
        if (!$document instanceof StructuredDocumentInterface) {
            return;
        }

        foreach ($document->getChildren() as $child) {
            if (!$child->hasProperty('slug')) {
                continue;
            }
            $slug = $child->getProperty('slug');
            $slugPart = '/' === $oldSlug ? '' : $oldSlug;
            if (0 !== strpos($slug, $slugPart.'/')) {
                continue;
            }
            $newChildSlug = '/'.trim($newSlug.substr($slug, strlen($slugPart)), '/');
            $child->setProperty('slug', $newChildSlug);
            $this->slugUpdated($child, $slug, $newChildSlug);
        }
    }
}
