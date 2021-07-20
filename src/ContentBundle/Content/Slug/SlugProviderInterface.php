<?php

namespace Rabble\ContentBundle\Content\Slug;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface SlugProviderInterface
{
    public function provide(AbstractPersistenceDocument $document, string $title): string;
}