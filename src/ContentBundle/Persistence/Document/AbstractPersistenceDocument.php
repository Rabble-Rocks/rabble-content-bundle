<?php

namespace Rabble\ContentBundle\Persistence\Document;

use Rabble\ContentBundle\Annotation\NodeProperty;

abstract class AbstractPersistenceDocument
{
    public const ROOT_NODE = '/';

    /**
     * @NodeProperty("jcr:uuid")
     */
    protected string $uuid;

    /**
     * @NodeProperty("path", accessor="method")
     */
    protected string $path;

    /**
     * @NodeProperty("rabble:class")
     */
    protected string $documentClass;

    protected string $nodeName;

    /**
     * @NodeProperty("rabble:default_locale")
     */
    protected string $defaultLocale;

    protected array $properties = [];

    protected bool $dirty = false;

    public static function getOwnProperties(): array
    {
        return [];
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function getUuid(): ?string
    {
        return $this->uuid ?? null;
    }

    public function getPath(): ?string
    {
        return $this->path ?? null;
    }

    public function setPath(string $path): void
    {
        $this->dirty = !isset($this->path) || $path !== $this->path;
        $this->path = $path;
    }

    public function getNodeName(): ?string
    {
        return $this->nodeName ?? null;
    }

    public function setNodeName(string $nodeName): void
    {
        $this->dirty = !isset($this->nodeName) || $nodeName !== $this->path;
        $this->nodeName = $nodeName;
    }

    public function getDocumentClass(): string
    {
        return $this->documentClass;
    }

    public function setDocumentClass(string $documentClass): void
    {
        $this->dirty = !isset($this->documentClass) || $documentClass !== $this->documentClass;
        $this->documentClass = $documentClass;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): void
    {
        $this->dirty = !isset($this->defaultLocale) || $defaultLocale !== $this->defaultLocale;
        $this->defaultLocale = $defaultLocale;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->dirty = $properties !== $this->properties;
        $this->properties = $properties;
    }

    public function getProperty($property)
    {
        return $this->properties[$property] ?? null;
    }

    public function setProperty($property, $value): void
    {
        $this->dirty = !isset($this->properties[$property]) || $this->properties[$property] !== $value;
        $this->properties[$property] = $value;
    }

    public function hasProperty($property): bool
    {
        return isset($this->properties[$property]);
    }
}
