<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\Content\Slug\SlugProviderInterface;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class SlugType extends AbstractType
{
    private SlugProviderInterface $slugProvider;

    public function __construct(SlugProviderInterface $slugProvider)
    {
        $this->slugProvider = $slugProvider;
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (is_string($data) && '' !== $data) {
                return;
            }
            $form = $event->getForm();
            while (null !== $form && !$form->getData() instanceof AbstractPersistenceDocument) {
                $form = $form->getParent();
            }
            /** @var AbstractPersistenceDocument $document */
            $document = $form->getData();
            if (
                !$document instanceof ContentDocument
                || null !== $document->getUuid()
                || !$form->has('title')
                || null === $form->get('title')->getData()
                || !$document->getParent() instanceof AbstractPersistenceDocument
            ) {
                return;
            }
            $title = $form->get('title')->getData();
            $event->setData($this->slugProvider->provide($document->getParent(), $title));
        });
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
