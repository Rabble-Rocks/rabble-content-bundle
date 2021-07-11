<?php

namespace Rabble\ContentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UITabsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $panelDef = $container->findDefinition('rabble_content.form_ui.panel.tabbed');
        $tabs = $container->findTaggedServiceIds('rabble_content.form_ui.tab');
        $prioritizedTabs = [];
        foreach ($tabs as $id => $tags) {
            foreach ($tags as $tag) {
                $priority = $tag['priority'] ?? 0;
                if (!isset($prioritizedTabs[$priority])) {
                    $prioritizedTabs[$priority] = [];
                }
                $prioritizedTabs[$priority][] = $id;
            }
        }
        krsort($prioritizedTabs);
        foreach ($prioritizedTabs as $ids) {
            foreach ($ids as $id) {
                $panelDef->addMethodCall('addTab', [new Reference($id)]);
            }
        }
    }
}
