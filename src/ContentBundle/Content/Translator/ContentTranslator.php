<?php

namespace Rabble\ContentBundle\Content\Translator;

use Jackalope\Session;
use Rabble\ContentBundle\Content\Transformer\ContentTransformerInterface;
use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\FieldType\ContentBlockType;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Rabble\FieldTypeBundle\FieldType\FieldContainerInterface;

class ContentTranslator implements ContentTranslatorInterface
{
    protected ContentBlockManagerInterface $contentBlockManager;
    protected Session $session;
    protected ContentTransformerInterface $contentTransformer;
    protected DocumentFieldsProviderInterface $fieldsProvider;

    public function __construct(
        ContentBlockManagerInterface $contentBlockManager,
        Session $session,
        ContentTransformerInterface $contentTransformer,
        DocumentFieldsProviderInterface $fieldsProvider
    ) {
        $this->contentBlockManager = $contentBlockManager;
        $this->session = $session;
        $this->contentTransformer = $contentTransformer;
        $this->fieldsProvider = $fieldsProvider;
    }

    public function translate(AbstractPersistenceDocument $document, ?string $locale): void
    {
        $node = $this->session->getNode($document->getPath());
        $rawData = $this->contentTransformer->getData($node);
        $fields = $this->fieldsProvider->getFields($document);
        $data = [];
        foreach ($fields as $field) {
            if (!$field instanceof AbstractFieldType) {
                continue;
            }
            $data[$field->getName()] =
                $this->translateField($field, $rawData, $locale) ??
                $this->translateField($field, $rawData, $document->getDefaultLocale());
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

    public function localizeNodeData(AbstractPersistenceDocument $document, ?string $locale): array
    {
        $data = $document->getProperties();
        foreach ($document->getOwnProperties() as $property) {
            $getter = 'get'.ucfirst($property);
            $data[$property] = $document->{$getter}();
        }
        if (null === $locale) {
            return $data;
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

        return $localizedData;
    }

    protected function translateField(AbstractFieldType $field, array $data, ?string $locale)
    {
        if ($field instanceof FieldContainerInterface) {
            $fieldData = $data[$field->getName()] ?? [];
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
            $fieldData = $data[$field->getName()] ?? [];
            $localizedData = [];
            foreach ($fieldData as $item) {
                $itemData = [];
                if (!isset($item['rabble:content_block']) || !$this->contentBlockManager->has($item['rabble:content_block'])) {
                    return [];
                }
                $contentBlock = $this->contentBlockManager->get($item['rabble:content_block']);
                /** @var AbstractFieldType[] $fields */
                $fields = $contentBlock->getFields();
                foreach ($fields as $childField) {
                    $itemData[$childField->getName()] = $this->translateField($childField, $item, $locale);
                }
                $itemData['rabble:content_block'] = $item['rabble:content_block'];
                $localizedData[] = $itemData;
            }

            return $localizedData;
        }
        $fieldName = $field->getOption('translatable') ? sprintf('%s:%s-%s', self::PHPCR_NAMESPACE, $locale, $field->getName()) : $field->getName();

        return $data[$fieldName] ?? $data[$field->getName()] ?? null;
    }

    protected function localizeDataForField(AbstractFieldType $field, array $data, string $locale): array
    {
        if ($field instanceof FieldContainerInterface) {
            $localizedData = [$field->getName() => []];
            /** @var AbstractFieldType[] $fields */
            $fields = $field->getOption($field->getFieldsOption());
            foreach ($data[$field->getName()] ?? [] as $item) {
                $itemData = [];
                foreach ($fields as $childField) {
                    foreach ($this->localizeDataForField($childField, $item, $locale) as $fieldName => $value) {
                        $itemData[$fieldName] = $value;
                    }
                }
                $localizedData[$field->getName()][] = $itemData;

                return $localizedData;
            }
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
