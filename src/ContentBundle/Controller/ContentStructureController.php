<?php

namespace Rabble\ContentBundle\Controller;

use Rabble\ContentBundle\ContentType\ContentTypeManager;
use Rabble\ContentBundle\Persistence\Document\StructuredDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentStructureController extends AbstractController
{
    private ContentManager $contentManager;
    private ContentTypeManager $contentTypeManager;

    public function __construct(ContentManager $contentManager, ContentTypeManager $contentTypeManager)
    {
        $this->contentManager = $contentManager;
        $this->contentTypeManager = $contentTypeManager;
    }

    public function indexAction(): Response
    {
        /** @var StructuredDocument $rootNode */
        $rootNode = $this->contentManager->find(StructuredDocument::ROOT_NODE);
        if (null === $rootNode) {
            $rootNode = new StructuredDocument();
            $this->contentManager->persist($rootNode);
            $this->contentManager->flush();
        }

        return $this->render('@RabbleContent/ContentStructure/index.html.twig', [
            'rootNode' => $rootNode,
            'contentTypes' => $this->contentTypeManager->all(),
        ]);
    }

    public function setParentAction(Request $request): Response
    {
        $item = $request->query->get('item');
        $parent = $request->query->get('to');
        $sortOrder = $request->query->getInt('sortOrder');
        if (
            null === $item
            || null === $parent
            || null === ($item = $this->contentManager->find($item))
            || null === ($parent = $this->contentManager->find($parent))
            || !$item instanceof StructuredDocumentInterface
            || !$parent instanceof StructuredDocumentInterface
        ) {
            throw new NotFoundHttpException();
        }
        if ($item === $parent && $sortOrder === $item->getOrder()) {
            return new Response();
        }

        $item->setParent($parent);
        $item->setOrder($sortOrder);
        $this->contentManager->flush();

        return new Response('ok');
    }
}
