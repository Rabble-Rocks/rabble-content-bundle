<?php

namespace Rabble\ContentBundle\Persistence\Manager;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Contracts\Translation\LocaleAwareInterface;

interface ContentManagerInterface extends LocaleAwareInterface
{
    public function find(string $id): ?AbstractPersistenceDocument;

    public function persist(AbstractPersistenceDocument $document): void;

    public function contains(AbstractPersistenceDocument $document): bool;

    public function remove(AbstractPersistenceDocument $document): void;

    public function refresh(AbstractPersistenceDocument $document): void;

    public function flush(): void;
}
