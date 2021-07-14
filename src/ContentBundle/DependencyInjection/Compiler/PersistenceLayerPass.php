<?php

namespace Rabble\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PersistenceLayerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $hydratorDef = $container->findDefinition('rabble_content.document_hydrator');
        $pathProviderDef = $container->findDefinition('rabble_content.path_provider');
        $hydrators = $container->findTaggedServiceIds('rabble_content.document_hydrator');
        $pathProviders = $container->findTaggedServiceIds('rabble_content.path_provider');
        $prioritizedHydrators = $this->sortServicesPrioritized($hydrators);
        $prioritizedPathProviders = $this->sortServicesPrioritized($pathProviders);
        foreach ($prioritizedHydrators as $ids) {
            foreach ($ids as $id) {
                $hydratorDef->addMethodCall('addHydrator', [new Reference($id)]);
            }
        }
        foreach ($prioritizedPathProviders as $ids) {
            foreach ($ids as $id) {
                $pathProviderDef->addMethodCall('addProvider', [new Reference($id)]);
            }
        }
    }

    private function sortServicesPrioritized(array $services): array
    {
        $prioritizedServices = [];
        foreach ($services as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                if (!isset($prioritizedServices[$priority])) {
                    $prioritizedServices[$priority] = [];
                }
                $prioritizedServices[$priority][] = $id;
            }
        }
        krsort($prioritizedServices);

        return $prioritizedServices;
    }
}
