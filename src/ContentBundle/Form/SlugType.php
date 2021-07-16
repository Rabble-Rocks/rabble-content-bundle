<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SlugType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $parent = $view;
        do {
            $parent = $parent->parent;
            if (null === $parent) {
                throw new \LogicException('No persistence document was found.');
            }
        } while (!$parent->vars['data'] instanceof AbstractPersistenceDocument);
        $document = $parent->vars['data'];
        if (isset($parent['title'])) {
            $view->vars['title_id'] = $parent['title']->vars['id'];
        }
        $view->vars['document'] = $document;
    }

    public function getBlockPrefix()
    {
        return 'rabble_slug';
    }
}