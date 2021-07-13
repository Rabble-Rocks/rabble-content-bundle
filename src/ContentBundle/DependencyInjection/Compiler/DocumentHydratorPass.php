<?php

namespace Rabble\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DocumentHydratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $hydratorDef = $container->findDefinition('rabble_content.document_hydrator');
        $hydrators = $container->findTaggedServiceIds('rabble_content.document_hydrator');
        $prioritizedHydrators = [];
        foreach ($hydrators as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                if (!isset($prioritizedHydrators[$priority])) {
                    $prioritizedHydrators[$priority] = [];
                }
                $prioritizedHydrators[$priority][] = $id;
            }
        }
        krsort($prioritizedHydrators);
        foreach ($prioritizedHydrators as $ids) {
            foreach ($ids as $id) {
                $hydratorDef->addMethodCall('addHydrator', [new Reference($id)]);
            }
        }
    }
}
