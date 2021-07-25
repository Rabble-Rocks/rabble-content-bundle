<?php

namespace Rabble\ContentBundle\Content\Translator;

use Jackalope\Node;
use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\FieldType\ContentBlockType;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Rabble\FieldTypeBundle\FieldType\FieldContainerInterface;

class ContentTranslator implements ContentTranslatorInterface
{
    protected ContentBlockManagerInterface $contentBlockManager;
    protected DocumentFieldsProviderInterface $fieldsProvider;

    public function __construct(
        ContentBlockManagerInterface $contentBlockManager,
        DocumentFieldsProviderInterface $fieldsProvider
    ) {
        $this->contentBlockManager = $contentBlockManager;
        $this->fieldsProvider = $fieldsProvider;
    }

    public function translate(AbstractPersistenceDocument $document, string $locale, Node $node): void
    {
        $fields = $this->fieldsProvider->getFields($document);
        $data = [];
        foreach ($fields as $field) {
            if (!$field instanceof AbstractFieldType) {
                continue;
            }
            $data[$field->getName()] =
                $this->translateField($field, $node, $locale) ??
                $this->translateField($field, $node, $document->getDefaultLocale());
        }
        foreach ($data as $key => $value) {
            foreach ($document->getOwnProperties() as $property) {
                if ($property === $key) {
                    $setter = 'set'.ucfirst($property);
                    $document->{$setter}($value);
                    unset($data[$key]);
                }
            }
        }
        $document->setProperties($data);
    }

    public function setNodeData(AbstractPersistenceDocument $document, string $locale, Node $node): void
    {
        $data = $document->getProperties();
        foreach ($document->getOwnProperties() as $property) {
            $getter = 'get'.ucfirst($property);
            $data[$property] = $document->{$getter}();
        }
        $localizedData = [];
        $fields = $this->fieldsProvider->getFields($document);
        foreach ($fields as $field) {
            if (!$field instanceof AbstractFieldType) {
                $localizedData[$field->getName()] = $data[$field->getName()] ?? null;

                continue;
            }
            foreach ($this->localizeDataForField($field, $data, $locale) as $fieldName => $value) {
                $localizedData[$fieldName] = $value;
            }
        }
        foreach ($localizedData as $property => $value) {
            $this->setNodeValueFlat($node, $property, $value);
        }
    }

    /**
     * @param mixed $key
     *
     * @return mixed|Node
     */
    protected function getNodeValue(Node $node, $key)
    {
        if ($node->hasNode($key)) {
            return $node->getNode($key);
        }
        if ($node->hasProperty($key)) {
            return $node->getPropertyValue($key);
        }

        return null;
    }

    protected function getNodeValueFlat(Node $node, $key)
    {
        $nodeValue = $this->getNodeValue($node, $key);
        if (!$nodeValue instanceof Node) {
            return $nodeValue;
        }
        $data = [];
        /** @var Node $value */
        foreach (array_merge($nodeValue->getNodes()->getArrayCopy(), $nodeValue->getPropertiesValues()) as $name => $value) {
            $data[$name] = $this->getNodeValueFlat($nodeValue, $name);
        }

        return $data;
    }

    protected function setNodeValueFlat(Node $node, $key, $nodeValue): void
    {
        if (!is_iterable($nodeValue)) {
            $node->setProperty($key, $nodeValue);

            return;
        }
        /** @var Node $childNode */
        $childNode = $node->hasNode($key) ? $node->getNode($key) : $node->addNode($key);
        /** @var Node $existingChildNode */
        foreach ($childNode->getNodes() as $existingChildNode) {
            if (!isset($nodeValue[$existingChildNode->getName()]) && is_numeric($existingChildNode->getName())) {
                $existingChildNode->remove();
            }
        }
        foreach ($nodeValue as $name => $value) {
            $this->setNodeValueFlat($childNode, $name, $value);
        }
    }

    protected function translateField(AbstractFieldType $field, Node $node, ?string $locale)
    {
        if ($field instanceof FieldContainerInterface) {
            $fieldData = $this->getNodeValue($node, $field->getName()) ?? [];
            $localizedData = [];
            /** @var AbstractFieldType[] $fields */
            $fields = $field->getOption($field->getFieldsOption());
            foreach ($fieldData as $item) {
                $itemData = [];
                foreach ($fields as $childField) {
                    $itemData[$childField->getName()] = $this->translateField($childField, $item, $locale);
                }
                $localizedData[] = $itemData;
            }

            return $localizedData;
        }
        if ($field instanceof ContentBlockType) {
            $fieldData = $this->getNodeValue($node, $field->getName()) ?? [];
            $localizedData = [];
            foreach ($fieldData as $item) {
                $itemData = [];
                $contentBlock = $this->getNodeValue($item, 'rabble:content_block');
                if (!is_string($contentBlock) || !$this->contentBlockManager->has($contentBlock)) {
                    return [];
                }
                $contentBlock = $this->contentBlockManager->get($contentBlock);
                /** @var AbstractFieldType[] $fields */
                $fields = $contentBlock->getFields();
                foreach ($fields as $childField) {
                    $itemData[$childField->getName()] = $this->translateField($childField, $item, $locale);
                }
                $itemData['rabble:content_block'] = $contentBlock->getName();
                $localizedData[] = $itemData;
            }

            return $localizedData;
        }
        $fieldName = $field->getOption('translatable') ? sprintf('%s:%s-%s', self::PHPCR_NAMESPACE, $locale, $field->getName()) : $field->getName();

        return $this->getNodeValueFlat($node, $fieldName) ?? $this->getNodeValueFlat($node, $field->getName());
    }

    protected function localizeDataForField(AbstractFieldType $field, array $data, string $locale): array
    {
        if ($field instanceof FieldContainerInterface) {
            $localizedData = [$field->getName() => []];
            /** @var AbstractFieldType[] $fields */
            $fields = $field->getOption($field->getFieldsOption());
            $items = $data[$field->getName()] ?? [];
            foreach ($items as $item) {
                $itemData = [];
                foreach ($fields as $childField) {
                    foreach ($this->localizeDataForField($childField, $item, $locale) as $fieldName => $value) {
                        $itemData[$fieldName] = $value;
                    }
                }
                $localizedData[$field->getName()][] = $itemData;
            }

            return $localizedData;
        }
        if ($field instanceof ContentBlockType) {
            $localizedData = [$field->getName() => []];
            foreach ($data[$field->getName()] ?? [] as $item) {
                if (!isset($item['rabble:content_block']) || !$this->contentBlockManager->has($item['rabble:content_block'])) {
                    continue;
                }
                $contentBlock = $this->contentBlockManager->get($item['rabble:content_block']);
                $itemData = [];
                /** @var AbstractFieldType $childField */
                foreach ($contentBlock->getFields() as $childField) {
                    foreach ($this->localizeDataForField($childField, $item, $locale) as $fieldName => $value) {
                        $itemData[$fieldName] = $value;
                    }
                }
                $itemData['rabble:content_block'] = $item['rabble:content_block'];
                $localizedData[$field->getName()][] = $itemData;
            }

            return $localizedData;
        }
        if ($field->getOption('translatable')) {
            return [sprintf('%s:%s-%s', self::PHPCR_NAMESPACE, $locale, $field->getName()) => $data[$field->getName()] ?? null];
        }

        return [$field->getName() => $data[$field->getName()] ?? null];
    }
}
