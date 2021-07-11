<?php

namespace Rabble\ContentBundle\Content\Structure;

use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverCollection;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverInterface;
use Webmozart\Assert\Assert;

class StructureBuilder implements StructureBuilderInterface
{
    /** @var ValueResolverCollection<ValueResolverInterface> */
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
    public function build($content): array
    {
        if (is_array($content)) {
            return $this->buildForArray($content);
        }
        Assert::isInstanceOf($content, AbstractPersistenceDocument::class);
        $structure = [];
        $properties = $content->getProperties();
        foreach ($content->getOwnProperties() as $ownProperty) {
            $getter = 'get'.ucfirst($ownProperty);
            $properties[$ownProperty] = $content->{$getter}();
        }
        foreach ($this->fieldsProvider->getFields($content) as $field) {
            $value = $properties[$field->getName()] ?? null;
            foreach ($this->valueResolvers as $resolver) {
                if ($resolver->supports($field)) {
                    $value = $resolver->resolve($value, $field);

                    break;
                }
            }
            if (null === $value) {
                continue;
            }
            $structure[$field->getName()] = $value;
        }

        return $structure;
    }

    private function buildForArray(array $documents): array
    {
        $structure = [];
        foreach ($documents as $document) {
            $structure[] = $this->build($document);
        }

        return $structure;
    }
}
