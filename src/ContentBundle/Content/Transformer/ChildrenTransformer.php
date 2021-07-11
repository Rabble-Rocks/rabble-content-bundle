<?php

namespace Rabble\ContentBundle\Content\Transformer;

use Jackalope\Factory;
use Jackalope\Node;
use Jackalope\Session;

/**
 * Transforms array values as PHPCR children.
 *
 * @author Rachel Snijders
 */
class ChildrenTransformer implements ContentTransformerInterface
{
    private Session $session;
    private Factory $factory;
    private ContentTransformerInterface $contentTransformer;

    public function __construct(
        Session $session,
        ContentTransformerInterface $contentTransformer
    ) {
        $this->session = $session;
        $this->factory = new Factory();
        $this->contentTransformer = $contentTransformer;
    }

    public function setData(Node $node, array $data): void
    {
        $path = $node->getPath();
        $children = $node->getNodes();
        if ($this->isNumericArray($data)) {
            $data = array_values($data); // Reset array indexes if it is a numeric array.
        }
        foreach ($data as $key => $value) {
            $key = (string) $key;
            if ($this->isValueSupported($value)) {
                if ($children->offsetExists($key)) {
                    $child = $children->offsetGet($key);
                } else {
                    $childPath = "{$path}/".$key;
                    $child = new Node($this->factory, [
                        'jcr:primaryType' => 'nt:unstructured',
                        'jcr:mixinTypes' => ['mix:referenceable'],
                    ], $childPath, $this->session, $this->session->getObjectManager(), true);
                    $this->session->getObjectManager()->addNode($childPath, $child);
                }
                $this->contentTransformer->setData($child, $value);
            }
        }
        // Delete nodes that are not in the passed data.
        /** @var Node $childNode */
        foreach ($children as $childNode) {
            if (!isset($data[$childNode->getName()])) {
                $this->session->getObjectManager()->removeItem($childNode->getPath());
            }
        }
    }

    public function getData(Node $node): array
    {
        $data = [];
        /** @var Node $child */
        foreach ($node->getNodes() as $child) {
            $data[$child->getName()] = $this->contentTransformer->getData($child);
        }

        return $data;
    }

    private function isValueSupported($value): bool
    {
        return is_array($value);
    }

    private function isNumericArray(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (!is_numeric($key)) {
                return false;
            }
        }

        return true;
    }
}
