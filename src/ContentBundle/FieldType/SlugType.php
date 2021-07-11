<?php

namespace Rabble\ContentBundle\FieldType;

use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SlugType extends AbstractFieldType
{
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add($this->options['name'], TextType::class, array_merge($this->options['form_options'], [
            'block_prefix' => 'rabble_slug',
        ]));
    }
}
