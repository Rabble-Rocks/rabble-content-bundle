<?php

namespace Rabble\ContentBundle\Controller;

use Rabble\AdminBundle\EventListener\RouterContextSubscriber;
use Rabble\ContentBundle\ContentType\ContentTypeManager;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function indexAction(Request $request): Response
    {
        $this->contentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        /** @var StructuredDocument $rootNode */
        $rootNode = $this->contentManager->find(StructuredDocument::ROOT_NODE);
        if (null === $rootNode) {
            $rootNode = new StructuredDocument();
            $this->contentManager->persist($rootNode);
            $this->contentManager->flush();
        }
        $treeData = $this->buildTreeData($rootNode, 0, 1);

        return $this->render('@RabbleContent/ContentStructure/index.html.twig', [
            'rootNode' => $rootNode,
            'treeData' => $treeData,
            'contentTypes' => $this->contentTypeManager->all(),
        ]);
    }

    public function getTreeNode(string $parent): JsonResponse
    {
        $parent = $this->contentManager->find($parent);
        if (!$parent instanceof ContentDocument) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($this->buildTreeData($parent, 0, 0));
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

        return new Response($item->getProperty('slug'));
    }

    private function buildTreeData(StructuredDocument $document, int $depth, int $maxDepth): array
    {
        $data = [];
        foreach ($document->getChildren() as $child) {
            if (!$child instanceof ContentDocument) {
                continue;
            }
            $addUrls = [];
            foreach ($this->contentTypeManager->all() as $contentType) {
                $addUrls[$contentType->getName()] = $this->generateUrl('rabble_admin_content_create', ['contentType' => $contentType->getName(), 'parent' => $child->getUuid()]);
            }
            $item = [
                'id' => $child->getUuid(),
                'get' => $this->generateUrl('rabble_admin_content_structure_get_tree_node', ['parent' => $child->getUuid()]),
                'edit' => $this->generateUrl('rabble_admin_content_edit', ['contentType' => $child->getContentType(), 'content' => $child->getUuid()]),
                'delete' => $this->generateUrl('rabble_admin_content_delete', ['contentType' => $child->getContentType(), 'content' => $child->getUuid()]),
                'add' => $addUrls,
                'title' => $child->getTitle(),
                'expanded' => $depth < $maxDepth,
                'subtitle' => $child->hasProperty('slug') ? $child->getProperty('slug') : '',
            ];
            if ([] !== $child->getChildren()) {
                $item['children'] = $depth < $maxDepth ? $this->buildTreeData($child, $depth + 1, $maxDepth) : true;
            }
            $data[] = $item;
        }

        return $data;
    }
}
