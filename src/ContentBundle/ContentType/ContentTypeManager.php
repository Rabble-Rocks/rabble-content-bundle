<?php

namespace Rabble\ContentBundle\ContentType;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;

class ContentTypeManager implements ContentTypeManagerInterface
{
    /** @var ContentType[] */
    protected array $contentTypes = [];

    /**
     * ContentTypeManager constructor.
     *
     * @param ContentType[] $contentTypes
     */
    public function __construct(array $contentTypes = [])
    {
        foreach ($contentTypes as $contentType) {
            $this->add($contentType);
        }
    }

    public function add(ContentType $contentType): void
    {
        $this->contentTypes[$contentType->getName()] = $contentType;
    }

    public function has(string $name): bool
    {
        return isset($this->contentTypes[$name]);
    }

    /**
     * @return ContentType[]
     */
    public function findByTag(string $tag): array
    {
        $arr = [];
        foreach ($this->contentTypes as $contentType) {
            if ($contentType->hasTag($tag)) {
                $arr[$contentType->getName()] = $contentType;
            }
        }

        return $arr;
    }

    public function get(string $name): ContentType
    {
        return $this->contentTypes[$name];
    }

    public function all(): array
    {
        return $this->contentTypes;
    }

    public function remove(string $name): void
    {
        unset($this->contentTypes[$name]);
    }

    public function getFields(AbstractPersistenceDocument $document): ?array
    {
        if (!$document instanceof ContentDocument) {
            return null;
        }
        $contentType = $this->get($document->getContentType());

        return $contentType->getFields();
    }
}
