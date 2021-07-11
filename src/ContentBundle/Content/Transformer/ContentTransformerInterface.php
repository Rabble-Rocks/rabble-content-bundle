<?php

namespace Rabble\ContentBundle\Content\Transformer;

use Jackalope\Node;

/**
 * Transforms data from any form to safely populate a content node and retrieve it afterwards.
 *
 * @author Rachel Snijders
 */
interface ContentTransformerInterface
{
    public function setData(Node $node, array $data): void;

    public function getData(Node $node): array;
}
