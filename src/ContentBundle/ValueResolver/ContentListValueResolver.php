<?php

namespace Rabble\ContentBundle\ValueResolver;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermsQuery;
use Rabble\ContentBundle\Content\Structure\StructureBuilderInterface;
use Rabble\ContentBundle\FieldType\ContentListType;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;
use Rabble\FieldTypeBundle\ValueResolver\ValueResolverInterface;
use Webmozart\Assert\Assert;

class ContentListValueResolver implements ValueResolverInterface
{
    private StructureBuilderInterface $structureBuilder;
    /** @var ArrayCollection<IndexService> */
    private ArrayCollection $indexes;
    private ContentManagerInterface $contentManager;
    private array $visited = [];

    public function __construct(
        StructureBuilderInterface $structureBuilder,
        ArrayCollection $indexes,
        ContentManagerInterface $contentManager
    ) {
        $this->structureBuilder = $structureBuilder;
        $this->indexes = $indexes;
        $this->contentManager = $contentManager;
    }

    /**
     * @param mixed                              $value
     * @param ContentListType|FieldTypeInterface $fieldType
     */
    public function resolve($value, FieldTypeInterface $fieldType): array
    {
        Assert::nullOrIsArray($value);
        if (null === $value) {
            return [];
        }
        /** @var IndexService $index */
        $index = $this->indexes->get('content-'.$this->contentManager->getLocale());
        $search = $index->createSearch();
        if (isset($value['contentType'])) {
            $search->addQuery(new MatchQuery('contentType', $value['contentType']));
        }
        if (isset($value['children'])) {
            $search->addQuery(new TermQuery('parent.keyword', $value['children'], ['case_insensitive' => true]));
        }
        if ([] !== $this->visited) {
            $search->addQuery(new BoolQuery([
                BoolQuery::MUST_NOT => new TermsQuery('_id', $this->visited),
            ]));
        }
        $query = $search->toArray();
        if ([] === $query) {
            return [];
        }
        $results = $index->search($query);
        $orderedDocuments = [];
        $documents = [];
        foreach ($results['hits']['hits'] as $result) {
            $this->visited[] = $result['_id'];
            $document = $this->contentManager->find($result['_id']);
            if (null === $document) {
                continue;
            }
            if ($document instanceof StructuredDocumentInterface) {
                $orderedDocuments[] = $document;

                continue;
            }
            $documents[] = $document;
        }
        usort($orderedDocuments, function (StructuredDocumentInterface $a, StructuredDocumentInterface $b) {
            if ($a->getOrder() === $b->getOrder()) {
                return 0;
            }

            return $a->getOrder() > $b->getOrder() ? 1 : -1;
        });
        $documents = array_merge($orderedDocuments, $documents);
        $data = [];
        foreach ($documents as $document) {
            $structure = $this->structureBuilder->build($document);
            $data[] = array_merge([
                'id' => $document->getUuid(),
                'contentType' => $document instanceof ContentDocument ? $document->getContentType() : null,
                'title' => $document instanceof ContentDocument ? $document->getTitle() : null,
            ], $structure);
        }
        $this->visited = [];

        return $data;
    }

    public function supports(FieldTypeInterface $fieldType): bool
    {
        return $fieldType instanceof ContentListType;
    }
}
