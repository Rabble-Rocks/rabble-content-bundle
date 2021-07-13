<?php

namespace Rabble\ContentBundle\Persistence\Document;

use Rabble\ContentBundle\Annotation\NodeProperty;

abstract class AbstractPersistenceDocument
{
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
        $ownProperties = [];
        $reflectionClass = new \ReflectionClass(static::class);
        foreach ($reflectionClass->getProperties() as $property) {
            if (__CLASS__ !== $property->getDeclaringClass()->getName()) {
                $ownProperties[] = $property->getName();
            }
        }

        return $ownProperties;
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
        $this->path = $path;
    }

    public function getNodeName(): ?string
    {
        return $this->nodeName ?? null;
    }

    public function setNodeName(string $nodeName): void
    {
        $this->nodeName = $nodeName;
    }

    public function getDocumentClass(): string
    {
        return $this->documentClass;
    }

    public function setDocumentClass(string $documentClass): void
    {
        $this->documentClass = $documentClass;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): void
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->dirty = true;
        $this->properties = $properties;
    }

    public function getProperty($property)
    {
        return $this->properties[$property] ?? null;
    }

    public function setProperty($property, $value): void
    {
        $this->dirty = true;
        $this->properties[$property] = $value;
    }

    public function hasProperty($property): bool
    {
        return isset($this->properties[$property]);
    }
}
