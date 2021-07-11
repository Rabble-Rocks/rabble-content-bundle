<?php

namespace Rabble\ContentBundle\FieldType;

use Rabble\ContentBundle\Form\RabbleContentBlockCollectionType;
use Rabble\FieldTypeBundle\FieldType\AbstractFieldType;
use Rabble\FieldTypeBundle\Form\EventSubscriber\SortOrderSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentBlockType extends AbstractFieldType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('max_size', null);
        $resolver->setRequired('content_blocks');
        $resolver->setAllowedTypes('content_blocks', ['array']);
        $resolver->setAllowedTypes('max_size', ['null', 'int']);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $field = $builder->create($this->options['name'], RabbleContentBlockCollectionType::class, array_merge([
            'content_blocks' => $this->getOption('content_blocks'),
        ], array_merge($this->options['form_options'], [
            'max_size' => $this->options['max_size'],
        ])));
        $builder->add($field);
        $field->addEventSubscriber(new SortOrderSubscriber());
        $field->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            if (null === $maxSize = $this->options['max_size']) {
                return;
            }
            $event->setData(array_slice($event->getData(), 0, $maxSize));
        }, -1);
    }
}
