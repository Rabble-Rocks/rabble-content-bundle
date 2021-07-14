<?php

namespace Rabble\ContentBundle\Persistence\Document;

interface StructuredDocumentInterface
{
    public function getParent(): ?AbstractPersistenceDocument;

    public function setParent(?AbstractPersistenceDocument $parent): void;

    public function getChildren(): array;

    public function setChildren(array $children): void;

    public function getOrder(): int;

    public function setOrder(int $order): void;
}
