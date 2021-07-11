<?php

namespace Rabble\ContentBundle\DocumentFieldsProvider;

use Psr\Log\LoggerInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;

class ChainedDocumentFieldsProvider implements DocumentFieldsProviderInterface
{
    /** @var DocumentFieldsProviderInterface[] */
    private array $providers = [];
    private ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger, iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
        $this->logger = $logger;
    }

    public function addProvider(DocumentFieldsProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function getFields(AbstractPersistenceDocument $document): ?array
    {
        foreach ($this->providers as $provider) {
            $fields = $provider->getFields($document);
            if (null !== $fields) {
                return $fields;
            }
        }
        if (null !== $this->logger) {
            $this->logger->info(sprintf('No fields could be found for document with UUID \'%s\'', $document->getUuid()));
        }

        return [];
    }
}
