<?php

namespace Rabble\ContentBundle\ContentType;

use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;

class ContentType
{
    public const TRANSLATION_DOMAIN_ATTRIBUTE = 'translation_domain';

    protected string $name;
    /** @var string[] */
    protected array $tags = [];

    protected array $attributes = [];

    protected int $maxDepth;

    protected array $fields = [];

    public function __construct(string $name, array $tags = [], array $attributes = [], int $maxDepth = 0)
    {
        $this->name = $name;
        $this->tags = array_combine($tags, $tags);
        $this->attributes = $attributes;
        $this->maxDepth = $maxDepth;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = array_combine($tags, $tags);
    }

    public function addTag(string $tag): void
    {
        $this->tags[$tag] = $tag;
    }

    public function hasTag(string $tag): bool
    {
        return isset($this->tags[$tag]);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param null|mixed $default
     *
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * @param mixed $value
     */
    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function removeAttribute(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    public function setMaxDepth(int $maxDepth): void
    {
        $this->maxDepth = $maxDepth;
    }

    /**
     * @return FieldTypeInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
