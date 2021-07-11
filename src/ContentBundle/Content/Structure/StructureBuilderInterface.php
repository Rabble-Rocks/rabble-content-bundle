<?php

namespace Rabble\ContentBundle\Content\Structure;

use Rabble\ContentBundle\Persistence\Document\ContentDocument;

interface StructureBuilderInterface
{
    /**
     * @param array|ContentDocument $content
     */
    public function build($content): array;
}
