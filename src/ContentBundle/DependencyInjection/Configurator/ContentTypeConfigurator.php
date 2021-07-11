<?php

namespace Rabble\ContentBundle\DependencyInjection\Configurator;

use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\Event\DefaultFieldTypesEvent;
use Rabble\FieldTypeBundle\FieldType\Mapping\FieldTypeMappingCollection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ContentTypeConfigurator extends AbstractContentConfigurator
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, FieldTypeMappingCollection $fieldTypeMappings)
    {
        parent::__construct($fieldTypeMappings);
        $this->eventDispatcher = $eventDispatcher;
    }

    public function configure(ContentType $contentType)
    {
        $fieldConfigs = $contentType->getFields();
        $event = new DefaultFieldTypesEvent();
        $this->eventDispatcher->dispatch($event);
        $fields = $event->getFieldTypes();
        /** @var array $fieldConfig */
        foreach ($fieldConfigs as $fieldConfig) {
            $fields[] = $this->processField($fieldConfig);
        }
        $contentType->setFields($fields);
    }
}
