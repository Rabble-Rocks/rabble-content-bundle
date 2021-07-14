<?php

namespace Rabble\ContentBundle\DependencyInjection;

use ProxyManager\Configuration as ProxyConfiguration;
use ProxyManager\FileLocator\FileLocator as ProxyFileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Rabble\ContentBundle\ContentBlock\ContentBlock;
use Rabble\ContentBundle\ContentBlock\ContentBlockManager;
use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\ContentType\ContentTypeManager;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Rabble\ContentBundle\DependencyInjection\Configurator\ContentBlockConfigurator;
use Rabble\ContentBundle\DependencyInjection\Configurator\ContentTypeConfigurator;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\UI\Tab\ContentTab;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

class RabbleContentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.xml');

        $this->registerContentTypeConfigurator($container);
        $this->registerContentBlockConfigurator($container);
        $this->registerContentTypeManager($config, $container);
        $this->registerContentBlockManager($config, $container);
        $this->registerContentTabs($config, $container);
        $this->registerFormTheme($container);
        $this->registerIndexable($container);
        $this->registerProxyConfig($container);
    }

    private function registerContentTypeConfigurator(ContainerBuilder $container)
    {
        $configuratorDef = new Definition(ContentTypeConfigurator::class, [
            new Reference('event_dispatcher'),
            new Reference('rabble_field_type.field_type_mapping_collection'),
        ]);
        $container->setDefinition(ContentTypeConfigurator::class, $configuratorDef);
    }

    private function registerContentBlockConfigurator(ContainerBuilder $container)
    {
        $configuratorDef = new Definition(ContentBlockConfigurator::class, [new Reference('rabble_field_type.field_type_mapping_collection')]);
        $container->setDefinition(ContentBlockConfigurator::class, $configuratorDef);
    }

    private function registerContentBlockManager(array $config, ContainerBuilder $container)
    {
        $contentBlockManagerDef = new Definition(ContentBlockManager::class);
        $container->setDefinition($contentTypeManagerId = 'rabble_content.content_block_manager', $contentBlockManagerDef);
        $container->addAliases([
            'content_block_manager' => $contentTypeManagerId,
            ContentBlockManagerInterface::class => $contentTypeManagerId,
            ContentBlockManager::class => $contentTypeManagerId,
        ]);
        $this->addContentBlocks($config, $container, $contentBlockManagerDef);
    }

    private function addContentBlocks(array $config, ContainerBuilder $container, Definition $contentBlockManagerDef)
    {
        foreach ($config['content_blocks'] as $contentBlock) {
            $contentBlockId = sprintf('rabble_content.content_block.%s', $contentBlock['name']);
            $container->setDefinition($contentBlockId, $contentBlockDef = new Definition(ContentBlock::class, [
                $contentBlock['name'],
                $contentBlock['attributes'],
            ]));
            $contentBlockDef->addMethodCall('setFields', [$contentBlock['fields']]);
            $contentBlockDef->setConfigurator([new Reference(ContentBlockConfigurator::class), 'configure']);
            $contentBlockManagerDef->addMethodCall('add', [new Reference($contentBlockId)]);
        }
    }

    private function registerContentTypeManager(array $config, ContainerBuilder $container)
    {
        $contentTypeManagerDef = new Definition(ContentTypeManager::class);
        $container->setDefinition($contentTypeManagerId = 'rabble_content.content_type_manager', $contentTypeManagerDef);
        $container->addAliases([
            'content_type_manager' => $contentTypeManagerId,
            ContentTypeManagerInterface::class => $contentTypeManagerId,
            ContentTypeManager::class => $contentTypeManagerId,
        ]);
        $contentTypeManagerDef->addTag('rabble_content.document_fields_provider');
        $this->addContentTypes($config, $container, $contentTypeManagerDef);
    }

    private function addContentTypes(array $config, ContainerBuilder $container, Definition $contentTypeManagerDef)
    {
        foreach ($config['content_types'] as $contentType) {
            $contentTypeId = sprintf('rabble_content.content_type.%s', $contentType['name']);
            $container->setDefinition($contentTypeId, $contentTypeDef = new Definition(ContentType::class, [
                $contentType['name'],
                $contentType['tags'],
                $contentType['attributes'],
                $contentType['max_depth'],
            ]));
            $contentTypeDef->addMethodCall('setFields', [$contentType['fields']]);
            $contentTypeDef->setConfigurator([new Reference(ContentTypeConfigurator::class), 'configure']);
            $contentTypeManagerDef->addMethodCall('add', [new Reference($contentTypeId)]);
        }
    }

    private function registerFormTheme(ContainerBuilder $container): void
    {
        $resources = $container->hasParameter('twig.form.resources') ?
            $container->getParameter('twig.form.resources') : [];

        $resources[] = '@RabbleContent/Form/fields.html.twig';
        $container->setParameter('twig.form.resources', $resources);
    }

    private function registerIndexable(ContainerBuilder $container): void
    {
        $indexables = $container->hasParameter('rabble_content.indexables') ?
            $container->getParameter('rabble_content.indexables') : [];

        $indexables['content'] = ContentDocument::class;
        $container->setParameter('rabble_content.indexables', $indexables);
    }

    private function registerContentTabs(array $config, ContainerBuilder $container): void
    {
        foreach ($config['content_types'] as $contentType) {
            $attributes = $contentType['attributes'];
            foreach ($attributes['tabs'] ?? [] as $id => $options) {
                $options['contentType'] = $contentType['name'];
                $priority = 128;
                if (isset($options['priority'])) {
                    $priority = $options['priority'];
                    unset($options['priority']);
                }
                if (!isset($options['contentTemplate'])) {
                    $options['contentTemplate'] = '@RabbleContent/Content/Tab/default.html.twig';
                }
                $tabDefinition = new Definition(ContentTab::class, [
                    new Reference('translator.default'),
                    $options,
                ]);
                $tabDefinition->addTag('rabble_content.form_ui.tab', ['priority' => $priority]);
                $container->setDefinition('rabble_content.form_ui.tab.custom.'.$id, $tabDefinition);
            }
        }
    }

    private function registerProxyConfig(ContainerBuilder $container)
    {
        $configDef = new Definition(ProxyConfiguration::class);
        $proxyPath = $container->getParameter('kernel.cache_dir').'/rabble/ContentBundle/proxies';
        if (!is_dir($proxyPath)) {
            $filesystem = new Filesystem();
            $filesystem->mkdir($proxyPath);
        }
        $fileLocatorDef = new Definition(ProxyFileLocator::class, [$proxyPath]);
        $strategyDef = new Definition(FileWriterGeneratorStrategy::class, [$fileLocatorDef]);
        $configDef->addMethodCall('setGeneratorStrategy', [$strategyDef]);
        $configDef->addMethodCall('setProxiesTargetDir', [$proxyPath]);
        $container->setDefinition('rabble_content.proxy_configuration', $configDef);
    }
}
