<?php

namespace Rabble\ContentBundle\Persistence\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;
use Rabble\ContentBundle\Annotation\NodeName;
use Rabble\ContentBundle\Annotation\NodeProperty;

/**
 * @ES\Index()
 */
class ContentDocument extends StructuredDocument
{
    public const ROOT_NODE = '/content';

    /**
     * @NodeProperty("jcr:uuid")
     * @ES\Id
     */
    protected string $uuid;

    /**
     * @ES\Property
     */
    protected array $properties = [];

    /**
     * @NodeName.
     * @ES\Property(type="text", analyzer="case_insensitive", fields={"keyword"={"type"="keyword"}})
     */
    protected string $title;

    /**
     * @NodeProperty("rabble:content_type")
     */
    protected string $contentType;

    public static function getOwnProperties(): array
    {
        return ['title', 'contentType', 'parent', 'children'];
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->dirty = $this->dirty || !isset($this->title) || $title !== $this->title;
        $this->title = $title;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->dirty = $this->dirty || !isset($this->contentType) || $contentType !== $this->contentType;
        $this->contentType = $contentType;
    }
}
