<?php

namespace Rabble\ContentBundle\FieldType;

use Rabble\ContentBundle\Form\PageReferenceType as PageReferenceFormType;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\FormBuilderInterface;

class PageReferenceType extends AbstractFieldType
{
    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder->add($this->options['name'], PageReferenceFormType::class, $this->options['form_options']);
    }
}
