<?php

namespace Rabble\ContentBundle\FieldType;

use Rabble\ContentBundle\Form\ContentListConfigurationType;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentListType extends AbstractFieldType
{
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add($this->options['name'], ContentListConfigurationType::class, $this->options['form_options']);
    }
}
