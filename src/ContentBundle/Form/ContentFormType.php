<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\FieldTypeBundle\FieldType\FieldTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentFormType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fields');
        $resolver->setAllowedTypes('fields', ['array']);
        $resolver->setDefaults([
            'data_class' => AbstractPersistenceDocument::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AbstractPersistenceDocument $data */
        $data = $builder->getData();
        /** @var FieldTypeInterface[] $fields */
        $fields = $options['fields'];
        $propertiesBuilder = $builder->create('properties', null, ['compound' => true, 'label' => false]);
        foreach ($fields as $field) {
            if (property_exists($data, $field->getName())) {
                $field->buildForm($builder);

                continue;
            }
            $field->buildForm($propertiesBuilder);
        }
        $builder->add($propertiesBuilder);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FieldTypeInterface[] $fields */
        $fields = $options['fields'];
        foreach ($fields as $field) {
            $property = $view['properties'][$field->getName()] ?? $view[$field->getName()];
            $property->vars['component'] = $field->getComponent();
        }
    }
}
