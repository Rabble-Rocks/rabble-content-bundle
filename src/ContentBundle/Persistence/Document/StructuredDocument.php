<?php

namespace Rabble\ContentBundle\Persistence\Document;

use Rabble\ContentBundle\Annotation\NodeProperty;

class StructuredDocument extends AbstractPersistenceDocument implements StructuredDocumentInterface
{
    public const ROOT_NODE = '/structured-content';

    protected ?AbstractPersistenceDocument $parent;

    /**
     * @NodeProperty("rabble:order")
     */
    protected int $order = 0;

    /**
     * @var AbstractPersistenceDocument[]
     */
    protected array $children = [];

    public function __construct()
    {
        $this->nodeName = substr(self::ROOT_NODE, 1);
    }

    public static function getOwnProperties(): array
    {
        return ['parent', 'children', 'order'];
    }

    public function getParent(): ?AbstractPersistenceDocument
    {
        return $this->parent ?? null;
    }

    public function setParent(?AbstractPersistenceDocument $parent): void
    {
        $this->dirty = $this->dirty || !isset($this->parent) || $parent !== $this->parent;
        $this->parent = $parent;
    }

    /**
     * @return AbstractPersistenceDocument[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param AbstractPersistenceDocument[] $children
     */
    public function setChildren(array $children): void
    {
        $this->dirty = $this->dirty || $children !== $this->children;
        $this->children = $children;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): void
    {
        $this->dirty = $this->dirty || $order !== $this->order;
        $this->order = $order;
    }
}
