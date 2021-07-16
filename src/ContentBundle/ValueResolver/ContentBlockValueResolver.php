<?php

namespace Rabble\ContentBundle\ValueResolver;

use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\FieldType\ContentBlockType;
use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverCollection;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverInterface;
use Webmozart\Assert\Assert;

class ContentBlockValueResolver implements ValueResolverInterface
{
    private ValueResolverCollection $resolvers;
    private ContentBlockManagerInterface $contentBlockManager;

    public function __construct(
        ValueResolverCollection $resolvers,
        ContentBlockManagerInterface $contentBlockManager
    ) {
        $this->resolvers = $resolvers;
        $this->contentBlockManager = $contentBlockManager;
    }

    /**
     * @param mixed                               $value
     * @param ContentBlockType|FieldTypeInterface $fieldType
     */
    public function resolve($value, FieldTypeInterface $fieldType, ?string $target = null): array
    {
        Assert::isArray($value);
        $data = [];
        foreach ($value as $item) {
            if (!isset($item['rabble:content_block']) || !$this->contentBlockManager->has($item['rabble:content_block'])) {
                continue;
            }
            $contentBlock = $this->contentBlockManager->get($item['rabble:content_block']);
            $valueData = [];
            foreach ($contentBlock->getFields() as $field) {
                $value = $item[$field->getName()] ?? null;
                foreach ($this->resolvers as $resolver) {
                    if ($resolver->supports($field)) {
                        $value = $resolver->resolve($value, $field);

                        break;
                    }
                }
                $valueData[$field->getName()] = $value;
            }
            $valueData['block:type'] = $item['rabble:content_block'];
            $data[] = $valueData;
        }

        return $data;
    }

    public function supports(FieldTypeInterface $fieldType): bool
    {
        return $fieldType instanceof ContentBlockType;
    }
}
