<?php

namespace Rabble\ContentBundle\DependencyInjection\Compiler;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Rabble\DatatableBundle\Datatable\DataFetcher\ElasticsearchDataFetcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ElasticsearchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $locales = $container->getParameter('rabble_admin.locales');
        $indexables = $container->getParameter('rabble_content.indexables');
        $indexesOverride = $container->getParameter(Configuration::ONGR_INDEXES_OVERRIDE);
        $mappedIndexes = $container->getParameter(Configuration::ONGR_INDEXES);
        $indexDefinitions = [];

        /** @var DocumentParser $parser */
        $parser = $container->get(DocumentParser::class);
        foreach ($indexables as $key => $documentClass) {
            $reflectionClass = new \ReflectionClass($documentClass);

            $converterDefinition = $container->getDefinition(Converter::class);
            $converterDefinition->addMethodCall(
                'addClassMetadata',
                [
                    $documentClass,
                    $parser->getPropertyMetadata($reflectionClass),
                ]
            );
            $indexCollection = new Definition(ArrayCollection::class);

            $document = $parser->getIndexAnnotation($reflectionClass);
            $indexMetadata = $parser->getIndexMetadata($reflectionClass);

            $indexMetadata['settings'] = array_filter(
                array_replace_recursive(
                    $indexMetadata['settings'] ?? [],
                    [
                        'number_of_replicas' => $document->numberOfReplicas,
                        'number_of_shards' => $document->numberOfShards,
                    ],
                    $indexesOverride[$documentClass]['settings'] ?? []
                ),
                function ($value) {
                    if (0 === $value) {
                        return true;
                    }

                    return (bool) $value;
                }
            );

            $dataFetchers = [];
            foreach ($locales as $locale) {
                $indexAlias = $key.'-'.$locale;
                $indexSettings = new Definition(
                    IndexSettings::class,
                    [
                        $documentClass,
                        $indexAlias,
                        $indexAlias,
                        $indexMetadata,
                        $indexesOverride[$documentClass]['hosts'] ?? $document->hosts,
                        $indexesOverride[$documentClass]['default'] ?? $document->default,
                    ]
                );

                $indexServiceDefinition = new Definition(IndexService::class, [
                    $documentClass,
                    new Reference(Converter::class),
                    new Reference('event_dispatcher'),
                    $indexSettings,
                    $container->getParameter(Configuration::ONGR_PROFILER_CONFIG)
                        ? $container->getDefinition('ongr.esb.tracer') : null,
                ]);
                $indexServiceDefinition->setPublic(true);
                $indexCollection->addMethodCall('set', [$indexAlias, $indexServiceDefinition]);
                $container->setDefinition($serviceId = 'rabble_content.elasticsearch_index.'.$key.'.'.$locale, $indexServiceDefinition);
                $mappedIndexes[$indexAlias] = $serviceId;
                $indexDefinitions[] = $indexServiceDefinition;

                $dataFetcherDefinition = new Definition(ElasticsearchDataFetcher::class, [$indexServiceDefinition]);
                $dataFetchers[$locale] = $dataFetcherDefinition;
            }
            $container->setDefinition('elasticsearch_data_fetchers.'.$key, new Definition(ArrayCollection::class, [$dataFetchers]));
            $container->setDefinition('elasticsearch_index.collection.'.$key, $indexCollection);
        }
        $container->setDefinition('elasticsearch_index.collection', new Definition(ArrayCollection::class, [$indexDefinitions]));

        $container->setParameter(Configuration::ONGR_INDEXES, $mappedIndexes);
    }
}
