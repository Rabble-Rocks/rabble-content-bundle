<?php

namespace Rabble\ContentBundle\Content\Transformer;

use Jackalope\Node;

/**
 * Transforms values that are supported by Jackalope Node properties.
 *
 * @author Rachel Snijders
 */
class PropertyTransformer implements ContentTransformerInterface
{
    public function setData(Node $node, array $data): void
    {
        foreach ($data as $key => $value) {
            if ($this->isValueSupported($value)) {
                $node->setProperty((string) $key, $value);
            }
        }
    }

    public function getData(Node $node): array
    {
        return $node->getPropertiesValues();
    }

    private function isValueSupported($value): bool
    {
        return null === $value || is_scalar($value);
    }
}
