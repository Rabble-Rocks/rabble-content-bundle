<?php

namespace Rabble\ContentBundle\Persistence\Provider;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface PathProviderInterface
{
    public function provide(AbstractPersistenceDocument $document): string;
}
