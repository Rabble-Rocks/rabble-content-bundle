<?php

namespace Rabble\ContentBundle\Content\Transformer;

use Jackalope\Node;

class ContentTransformerChain implements ContentTransformerInterface
{
    /**
     * @var ContentTransformerInterface[]
     */
    private array $transformers;

    public function __construct(array $transformers = [])
    {
        $this->transformers = $transformers;
    }

    public function addTransformer(ContentTransformerInterface $transformer): void
    {
        $this->transformers[] = $transformer;
    }

    public function setData(Node $node, array $data): void
    {
        foreach ($this->transformers as $transformer) {
            $transformer->setData($node, $data);
        }
    }

    public function getData(Node $node): array
    {
        $data = [];
        foreach ($this->transformers as $transformer) {
            $transformedData = $transformer->getData($node);
            foreach ($transformedData as $key => $value) {
                if (false === strpos($key, 'jcr:')) {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }
}
