<?php

namespace Rabble\ContentBundle\Datatable;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Search;
use Rabble\AdminBundle\EventListener\RouterContextSubscriber;
use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\DatatableBundle\Datatable\AbstractGenericDatatable;
use Rabble\DatatableBundle\Datatable\DataFetcher\ElasticsearchDataFetcher;
use Rabble\DatatableBundle\Datatable\Row\Data\Column\Action\Action;
use Rabble\DatatableBundle\Datatable\Row\Data\Column\ActionDataColumn;
use Rabble\DatatableBundle\Datatable\Row\Data\Column\GenericDataColumn;
use Rabble\DatatableBundle\Datatable\Row\Heading\Column\GenericHeadingColumn;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class ContentDatatable extends AbstractGenericDatatable
{
    private RequestStack $requestStack;
    private RouterInterface $router;
    /** @var ArrayCollection<ElasticsearchDataFetcher> */
    private array $dataFetchers;

    public function __construct(
        RequestStack $requestStack,
        RouterInterface $router,
        ArrayCollection $dataFetchers
    ) {
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->dataFetchers = $dataFetchers->toArray();
    }

    public function initialize(): void
    {
        $request = $this->requestStack->getMainRequest();
        $locale = $request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY);
        $this->setConfiguration(['data_fetcher' => $this->dataFetchers[$locale] ?? current($this->dataFetchers)]);
        $contentType = $request->get('contentType');
        $contentType = $contentType instanceof ContentType ? $contentType->getName() : $contentType;
        if (!is_string($contentType)) {
            return;
        }
        $this->headingColumns = [
            new GenericHeadingColumn('', false, ['style' => ['width' => 60], 'data-sortable' => 'false']),
            new GenericHeadingColumn('table.content.title', 'RabbleContentBundle'),
        ];
        $this->dataColumns = [
            new ActionDataColumn([
                'actions' => [
                    new Action(
                        'Routing.generate("rabble_admin_content_edit", {contentType: "'.addcslashes($contentType, "'").'", content: data["id"]})',
                        'pencil'
                    ),
                    new Action(
                        'Routing.generate("rabble_admin_content_delete", {contentType: "'.addcslashes($contentType, "'").'", content: data["id"]})',
                        'trash',
                        true,
                        [
                            'class' => 'btn-danger',
                            'data-confirm' => '?Translator.trans("content.delete_confirm", [], "RabbleContentBundle")',
                            'data-reload-datatable' => $this->getName(),
                        ]
                    ),
                ],
            ]),
            new GenericDataColumn([
                'expression' => 'data["title"]',
                'searchField' => 'title',
                'sortField' => 'title.keyword',
            ]),
        ];
        $this->getEventDispatcher()->addListener(sprintf('datatable.%s.before_fetch_data', $this->getName()), [$this, 'beforeFetchData']);
    }

    public function render(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $contentType = $request->attributes->get('contentType');
        if (!$contentType instanceof ContentType) {
            return parent::render();
        }
        $this->setOptions(['ajax' => $this->router->generate('rabble_datatable_table_localized', [
            'datatable' => $this->getName(),
            'contentType' => $contentType->getName(),
            RouterContextSubscriber::CONTENT_LOCALE_KEY => $request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY),
        ])]);

        return parent::render();
    }

    public function beforeFetchData(GenericEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $contentType = $request->query->get('contentType');
        if (null === $contentType) {
            return;
        }
        /** @var Search $search */
        $search = $event->getSubject();
        $search->addQuery(new MatchQuery('contentType', $contentType));
    }
}
