<?php

namespace Rabble\ContentBundle\DependencyInjection\Configurator;

use Rabble\FieldTypeBundle\FieldType\FieldContainerInterface;
use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;
use Rabble\FieldTypeBundle\FieldType\Mapping\FieldTypeMappingCollection;

abstract class AbstractContentConfigurator
{
    protected FieldTypeMappingCollection $fieldTypeMappings;

    public function __construct(FieldTypeMappingCollection $fieldTypeMappings)
    {
        $this->fieldTypeMappings = $fieldTypeMappings;
    }

    protected function configureConstraints(array $constraintConfigs): array
    {
        $constraints = [];
        foreach ($constraintConfigs as $constraintConfig) {
            $class = $constraintConfig['class'];
            if (!preg_match('/\\\\/', $class)) {
                $class = 'Symfony\\Component\\Validator\\Constraints\\'.$class;
            }
            $constraints[] = new $class($constraintConfig['options']);
        }

        return $constraints;
    }

    protected function processField(array $fieldConfig): FieldTypeInterface
    {
        $type = (string) ($this->fieldTypeMappings[$fieldConfig['type']] ?? $fieldConfig['type']);
        unset($fieldConfig['type']);
        if (isset($fieldConfig['constraints'])) {
            $fieldConfig['constraints'] = $this->configureConstraints($fieldConfig['constraints']);
        }
        if (in_array(FieldContainerInterface::class, class_implements($type), true)) {
            $fieldsOption = call_user_func([$type, 'getFieldsOption']);
            $subFields = $fieldConfig[$fieldsOption];
            $processedSubFields = [];
            foreach ($subFields as $subField) {
                $processedSubFields[] = $this->processField($subField);
            }
            $fieldConfig[$fieldsOption] = $processedSubFields;
        }

        return new $type($fieldConfig);
    }
}
