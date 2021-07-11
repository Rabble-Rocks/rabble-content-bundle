<?php

namespace Rabble\ContentBundle\Content\EventListener;

use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\FieldType\ContentBlockType;
use Rabble\ContentBundle\Persistence\Event\RemoveEvent;
use Rabble\ContentBundle\Persistence\Event\UpdateEvent;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Rabble\FieldTypeBundle\FieldType\FieldContainerInterface;
use Rabble\FieldTypeBundle\FieldType\ImageType;
use Rabble\FieldTypeBundle\Model\FileValue;
use Rabble\FieldTypeBundle\VichUploader\PropertyMappingFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Removes images assigned to content when they're changed / removed.
 */
class ContentImageSubscriber implements EventSubscriberInterface
{
    private DocumentFieldsProviderInterface $fieldsProvider;
    private ContentBlockManagerInterface $contentBlockManager;
    private PropertyMappingFactory $propertyMappingFactory;
    private StorageInterface $storage;

    private array $imagesToRemove = [];
    private array $imagesToKeep = [];

    public function __construct(
        DocumentFieldsProviderInterface $fieldsProvider,
        ContentBlockManagerInterface $contentBlockManager,
        PropertyMappingFactory $propertyMappingFactory,
        StorageInterface $storage
    ) {
        $this->fieldsProvider = $fieldsProvider;
        $this->contentBlockManager = $contentBlockManager;
        $this->propertyMappingFactory = $propertyMappingFactory;
        $this->storage = $storage;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            UpdateEvent::class => ['onUpdate', -128],
            RemoveEvent::class => ['onRemove', -128],
        ];
    }

    public function onUpdate(UpdateEvent $event): void
    {
        $document = $event->getDocument();
        if ($event->isPrevented()) {
            return;
        }
        $fields = $this->fieldsProvider->getFields($document);
        $old = $event->getOldProperties();
        $new = $event->getNewProperties();
        foreach ($fields as $field) {
            if ($field instanceof AbstractFieldType) {
                $this->removeImagesFromField($field, $old, $new);
            }
        }
        $this->doRemove();
    }

    public function onRemove(RemoveEvent $event): void
    {
        $document = $event->getDocument();
        if ($event->isPrevented()) {
            return;
        }
        $fields = $this->fieldsProvider->getFields($document);
        foreach ($fields as $field) {
            if ($field instanceof AbstractFieldType) {
                $this->removeImagesFromField($field, $document->getProperties(), []);
            }
        }
        $this->doRemove();
    }

    /**
     * We remove all images last, because in collection and block fields, we have the
     * ability to change the order of the items. This causes the listener index to get
     * confused which images to remove, because the old properties don't match up with
     * the new properties.
     */
    private function doRemove(): void
    {
        foreach (array_diff_key($this->imagesToRemove, $this->imagesToKeep) as $file => $mapping) {
            $this->storage->remove(new FileValue($file), $mapping);
        }
        $this->imagesToRemove = [];
        $this->imagesToKeep = [];
    }

    private function removeImagesFromField(AbstractFieldType $field, array $old, array $new): void
    {
        if ($field instanceof ImageType) {
            if (isset($new[$field->getName()])) {
                $this->imagesToKeep[$new[$field->getName()]] = true;
            }
            if (isset($old[$field->getName()]) && (!isset($new[$field->getName()]) || $old[$field->getName()] !== $new[$field->getName()])) {
                $file = $old[$field->getName()];
                $mapping = $this->propertyMappingFactory->fromMappingName($field->getOption('mapping'));
                $this->imagesToRemove[$file] = $mapping;
            }
        }
        if ($field instanceof FieldContainerInterface) {
            /** @var AbstractFieldType[] $fields */
            $fields = $field->getOption($field->getFieldsOption());
            foreach ($fields as $subField) {
                $iterator = count($old[$field->getName()] ?? []) > count($new[$field->getName()] ?? [])
                    ? $old[$field->getName()] : $new[$field->getName()] ?? [];
                foreach ($iterator ?? [] as $i => $item) {
                    $subOld = $old[$field->getName()][$i] ?? [];
                    $subNew = $new[$field->getName()][$i] ?? [];
                    $this->removeImagesFromField($subField, $subOld, $subNew);
                }
            }
        }
        if ($field instanceof ContentBlockType) {
            $iterator = count($old[$field->getName()] ?? []) > count($new[$field->getName()] ?? [])
                ? $old[$field->getName()] : $new[$field->getName()] ?? [];
            foreach ($iterator ?? [] as $i => $item) {
                $blockType = $item['rabble:content_block'] ?? null;
                if (null === $blockType || !$this->contentBlockManager->has($blockType)) {
                    continue;
                }
                $block = $this->contentBlockManager->get($blockType);
                /** @var AbstractFieldType $subField */
                foreach ($block->getFields() as $subField) {
                    $subOld = $old[$field->getName()][$i] ?? [];
                    $subNew = $new[$field->getName()][$i] ?? [];
                    $this->removeImagesFromField($subField, $subOld, $subNew);
                }
            }
        }
    }
}
