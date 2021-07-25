<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Jackalope\Node;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;

class StructuredContentHydrator implements DocumentHydratorInterface
{
    private ContentManager $contentManager;

    public function __construct(ContentManager $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function hydrateDocument(AbstractPersistenceDocument $document, Node $node): void
    {
        if (!$document instanceof StructuredDocumentInterface) {
            return;
        }
        /** @var StructuredDocumentInterface[] $children */
        $childNodes = [];
        /** @var Node $childNode */
        foreach ($node->getNodes() as $childNode) {
            if ($childNode->hasProperty('rabble:class')) {
                $className = $childNode->getPropertyValue('rabble:class');
                if (
                    StructuredDocumentInterface::class === $className
                    || is_subclass_of($className, StructuredDocumentInterface::class)
                ) {
                    $childNodes[] = $childNode;
                }
            }
        }
        usort($childNodes, function (Node $a, Node $b) {
            $orderA = $a->hasProperty('rabble:order') ? $a->getProperty('rabble:order') : 0;
            $orderB = $b->hasProperty('rabble:order') ? $b->getProperty('rabble:order') : 0;
            if ($orderA === $orderB) {
                return 0;
            }

            return $orderA > $orderB ? 1 : -1;
        });
        $document->setChildren(array_map(function (Node $child) {
            return $this->contentManager->find($child->getPath());
        }, $childNodes));
        $parent = $node->getParent();
        $parentId = $parent->hasProperty('jcr:uuid') ? $parent->getPropertyValue('jcr:uuid') : $parent->getPath();

        if ($parent->hasProperty('rabble:class')) {
            $className = $parent->getPropertyValue('rabble:class');
            if (
                AbstractPersistenceDocument::class === $className
                || is_subclass_of($className, AbstractPersistenceDocument::class)
            ) {
                $document->setParent($this->contentManager->find($parentId));
            }
        }
    }

    public function hydrateNode(AbstractPersistenceDocument $document, Node $node): void
    {
        if (!$document instanceof StructuredDocumentInterface) {
            return;
        }
        $parent = $document->getParent();
        if (null === $parent) {
            return;
        }
        $session = $this->contentManager->getSession();
        $oldDocument = clone $document;
        $this->contentManager->refresh($oldDocument);
        $oldParent = $oldDocument->getParent();
        if ($oldParent !== $parent && $oldParent instanceof StructuredDocumentInterface) {
            /** @var StructuredDocumentInterface[] $children */
            $children = $oldParent->getChildren();
            $index = array_search($document, $children, true);
            if (false !== $index) {
                unset($children[$index]);
            }
            $children = array_values($children);
            foreach ($children as $i => $child) {
                $node = $session->getNode($child->getPath());
                $node->setProperty('rabble:order', $i);
            }
        }
        if (Node::STATE_NEW !== $node->getState()) {
            $this->contentManager->move($document, $parent->getPath().'/'.$document->getNodeName());
        }
        if ($parent instanceof StructuredDocumentInterface) {
            /** @var StructuredDocumentInterface[] $children */
            $children = $parent->getChildren();
            $index = array_search($document, $children, true);
            if (false !== $index) {
                unset($children[$index]);
            }
            array_splice($children, $document->getOrder(), 0, [$document]);
            $children = array_values($children);
            foreach ($children as $i => $child) {
                $node = $session->getNode($child->getPath());
                $node->setProperty('rabble:order', $i);
            }
        }
    }
}
