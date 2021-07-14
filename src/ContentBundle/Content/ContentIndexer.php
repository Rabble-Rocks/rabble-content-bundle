<?php

namespace Rabble\ContentBundle\Content;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Rabble\ContentBundle\Content\Structure\StructureBuilder;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class ContentIndexer
{
    protected string $documentClass;
    protected StructureBuilder $structureBuilder;
    /** @var ArrayCollection<IndexService> */
    protected ArrayCollection $indexes;
    protected LocaleAwareInterface $localeAware;

    public function __construct(
        string $documentClass,
        ArrayCollection $indexes,
        StructureBuilder $structureBuilder,
        LocaleAwareInterface $localeAware
    ) {
        $this->documentClass = $documentClass;
        $this->structureBuilder = $structureBuilder;
        $this->indexes = $indexes;
        $this->localeAware = $localeAware;
    }

    /**
     * Indexes a document into all indexes.
     */
    public function index(AbstractPersistenceDocument $content): void
    {
        foreach ($this->indexes as $index) {
            $index->bulk('index', $this->getProperties($content));
        }
    }

    /**
     * Updates a document into the current index.
     */
    public function update(AbstractPersistenceDocument $content): void
    {
        $this->getIndex()->bulk('index', $this->getProperties($content));
    }

    /**
     * Removes a document from all indexes.
     */
    public function remove(AbstractPersistenceDocument $content): void
    {
        foreach ($this->indexes as $index) {
            $index->bulk('delete', ['_id' => $content->getUuid()]);
        }
    }

    /**
     * Flushes all indexes.
     */
    public function flush(array $params = []): void
    {
        foreach ($this->indexes as $index) {
            $index->flush($params);
        }
    }

    /**
     * Recreates the current index.
     */
    public function reset(): void
    {
        $this->getIndex()->dropAndCreateIndex();
    }

    /**
     * Commit changes to the current index.
     */
    public function commit(string $commitMode = 'refresh', array $params = []): void
    {
        $this->getIndex()->commit($commitMode, $params);
    }

    /**
     * Commit changes to all indexes.
     */
    public function commitAll(string $commitMode = 'refresh', array $params = []): void
    {
        foreach ($this->indexes as $index) {
            $index->commit($commitMode, $params);
        }
    }

    public function supports(AbstractPersistenceDocument $document): bool
    {
        return $document instanceof $this->documentClass;
    }

    protected function getProperties(AbstractPersistenceDocument $document): array
    {
        $structureProperties = $this->structureBuilder->build($document);
        $ownProperties = $document->getOwnProperties();
        $properties = [];
        foreach ($ownProperties as $ownProperty) {
            if (isset($structureProperties[$ownProperty])) {
                $properties[$ownProperty] = $structureProperties[$ownProperty];
                unset($structureProperties[$ownProperty]);
            }
        }
        $properties['_id'] = $document->getUuid();
        $properties['properties'] = $structureProperties;

        return $properties;
    }

    protected function getIndex(): IndexService
    {
        return $this->indexes[static::getIndexName($this->localeAware->getLocale())];
    }

    protected static function getIndexName(string $locale): string
    {
        return 'content-'.$locale;
    }
}
