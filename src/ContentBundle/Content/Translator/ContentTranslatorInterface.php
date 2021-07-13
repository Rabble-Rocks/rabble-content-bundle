<?php

namespace Rabble\ContentBundle\Content\Translator;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

interface ContentTranslatorInterface
{
    public const PHPCR_NAMESPACE = 'rabble_translated';
    public const PHPCR_NAMESPACE_URI = 'http://rabble.rocks/namespace/rabble_translated';

    public function translate(AbstractPersistenceDocument $document, string $locale): void;

    public function setNodeData(AbstractPersistenceDocument $document, string $locale): void;
}
