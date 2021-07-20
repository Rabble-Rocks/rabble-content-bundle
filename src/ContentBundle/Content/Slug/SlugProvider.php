<?php

namespace Rabble\ContentBundle\Content\Slug;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugProvider implements SlugProviderInterface
{
    private SluggerInterface $slugger;

    public function __construct(
        SluggerInterface $slugger
    ) {
        $this->slugger = $slugger;
    }

    public function provide(AbstractPersistenceDocument $document, string $title): string
    {
        $slug = '/';
        $slugNodes = [];
        if (!$document->hasProperty('slug')) {
            return $slug;
        }
        $contentSlug = $document->getProperty('slug');
        if ('/' !== $contentSlug) {
            $slugNodes[] = substr($contentSlug, 1);
        }
        $slugNodes[] = $this->slugger->slug($title);
        $slug .= strtolower(implode('/', $slugNodes));

        return $slug;
    }
}
