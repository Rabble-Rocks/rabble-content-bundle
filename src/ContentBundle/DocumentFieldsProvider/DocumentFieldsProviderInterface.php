<?php

namespace Rabble\ContentBundle\DocumentFieldsProvider;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;

interface DocumentFieldsProviderInterface
{
    /**
     * @return null|FieldTypeInterface[]
     */
    public function getFields(AbstractPersistenceDocument $document): ?array;
}
