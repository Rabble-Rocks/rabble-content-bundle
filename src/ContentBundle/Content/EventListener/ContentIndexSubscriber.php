<?php

namespace Rabble\ContentBundle\Content\EventListener;

use Rabble\ContentBundle\Content\ContentIndexer;
use Rabble\ContentBundle\Persistence\Event\AfterSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentIndexSubscriber implements EventSubscriberInterface
{
    /** @var ContentIndexer[]|iterable */
    private iterable $indexers;

    public function __construct(iterable $indexers)
    {
        $this->indexers = $indexers;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterSaveEvent::class => ['afterSave', -128],
        ];
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
}
