<?php

namespace Rabble\ContentBundle\Persistence\Provider\NodeName;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface NodeNameProviderInterface
{
    public function provide(AbstractPersistenceDocument $document): string;
}
