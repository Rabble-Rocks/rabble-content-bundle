<?php

namespace Rabble\ContentBundle\Controller\Action;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use Rabble\AdminBundle\EventListener\RouterContextSubscriber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PageResolverAction
{
    private ArrayCollection $indexes;

    public function __construct(ArrayCollection $indexes)
    {
        $this->indexes = $indexes;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $locale = $request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY);
        $query = $request->query->get('q');
        if (!$this->indexes->containsKey('content-'.$locale) || strlen($query) < 2) {
            return new JsonResponse();
        }
        /** @var IndexService $index */
        $index = $this->indexes->get('content-'.$locale);
        $search = $index->createSearch();
        $search->addQuery($bool = new BoolQuery());
        $bool->add(new WildcardQuery('title', "*{$query}*"));
        $results = $index->search($search->toArray());
        $data = [];
        foreach ($results['hits']['hits'] as $hit) {
            $data[] = [
                'value' => $hit['_id'],
                'text' => $hit['_source']['title'] ?? 'undefined',
            ];
        }

        return new JsonResponse(['results' => $data]);
    }
}
