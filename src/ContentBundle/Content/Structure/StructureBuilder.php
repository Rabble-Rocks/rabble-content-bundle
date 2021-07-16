<?php

namespace Rabble\ContentBundle\Content\Structure;

use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverCollection;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverInterface;
use Webmozart\Assert\Assert;

class StructureBuilder implements StructureBuilderInterface
{
    public const TARGET_ELASTICSEARCH = 'elasticsearch';
    public const TARGET_WEBSITE = 'website';

    /** @var ValueResolverCollection<ValueResolverInterface>|ValueResolverInterface[] */
    protected ValueResolverCollection $valueResolvers;
    protected DocumentFieldsProviderInterface $fieldsProvider;

    public function __construct(
        ValueResolverCollection $valueResolvers,
        DocumentFieldsProviderInterface $fieldsProvider
    ) {
        $this->valueResolvers = $valueResolvers;
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param AbstractPersistenceDocument|AbstractPersistenceDocument[] $content
     */
    public function build($content, ?string $target = null): array
    {
        if (is_array($content)) {
            return $this->buildForArray($content, $target);
        }
        Assert::isInstanceOf($content, AbstractPersistenceDocument::class);
        $structure = [];
        $properties = $content->getProperties();
        foreach ($content->getOwnProperties() as $ownProperty) {
            $getter = 'get'.ucfirst($ownProperty);
            $value = $content->{$getter}();
            if ($value instanceof AbstractPersistenceDocument) {
                $value = $value->getUuid();
            }
            if (is_array($value)) {
                foreach ($value as $i => $item) {
                    if ($item instanceof AbstractPersistenceDocument) {
                        $value[$i] = $item->getUuid();
                    }
                }
            }
            $properties[$ownProperty] = $value;
            $structure[$ownProperty] = $value;
        }
        foreach ($this->fieldsProvider->getFields($content) as $field) {
            $value = $properties[$field->getName()] ?? null;
            foreach ($this->valueResolvers as $resolver) {
                if ($resolver->supports($field)) {
                    $value = $resolver->resolve($value, $field, $target);

                    break;
                }
            }
            if (null === $value) {
                continue;
            }
            $structure[$field->getName()] = $value;
        }
        if (self::TARGET_WEBSITE === $target) {
            $structure['id'] = $content->getUuid();
            $structure['title'] = $content instanceof ContentDocument ? $content->getTitle() : null;
            $structure['contentType'] = $content instanceof ContentDocument ? $content->getContentType() : null;
        }

        return $structure;
    }

    private function buildForArray(array $documents, ?string $target): array
    {
        $structure = [];
        foreach ($documents as $document) {
            $structure[] = $this->build($document, $target);
        }

        return $structure;
    }
}
