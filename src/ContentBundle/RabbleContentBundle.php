<?php

namespace Rabble\ContentBundle;

use Rabble\ContentBundle\DependencyInjection\Compiler\DocumentHydratorPass;
use Rabble\ContentBundle\DependencyInjection\Compiler\ElasticsearchPass;
use Rabble\ContentBundle\DependencyInjection\Compiler\UITabsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RabbleContentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ElasticsearchPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new UITabsPass());
        $container->addCompilerPass(new DocumentHydratorPass());
    }
}
