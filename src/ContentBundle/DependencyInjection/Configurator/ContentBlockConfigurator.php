<?php

namespace Rabble\ContentBundle\DependencyInjection\Configurator;

use Rabble\ContentBundle\ContentBlock\ContentBlock;

class ContentBlockConfigurator extends AbstractContentConfigurator
{
    public function configure(ContentBlock $contentBlock)
    {
        $fieldConfigs = $contentBlock->getFields();
        $fields = [];
        /** @var array $fieldConfig */
        foreach ($fieldConfigs as $fieldConfig) {
            $fields[] = $this->processField($fieldConfig);
        }
        $contentBlock->setFields($fields);
    }
}
