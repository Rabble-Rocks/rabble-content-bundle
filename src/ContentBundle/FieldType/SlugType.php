<?php

namespace Rabble\ContentBundle\FieldType;

use Rabble\ContentBundle\Form\SlugType as SlugFormType;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\FormBuilderInterface;

class SlugType extends AbstractFieldType
{
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add($this->options['name'], SlugFormType::class, $this->options['form_options']);
    }
}
