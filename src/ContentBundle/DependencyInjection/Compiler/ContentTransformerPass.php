<?php

namespace Rabble\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ContentTransformerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $transformerChainDef = $container->findDefinition('rabble_content.content.transformer_chain');
        $transformers = $container->findTaggedServiceIds('rabble_content_transformer');
        foreach ($transformers as $id => $tags) {
            $transformerChainDef->addMethodCall('addTransformer', [new Reference($id)]);
        }
    }
}
