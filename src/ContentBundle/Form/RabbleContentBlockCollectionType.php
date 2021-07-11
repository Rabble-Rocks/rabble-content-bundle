<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\ContentBlock\ContentBlock;
use Rabble\ContentBundle\ContentBlock\ContentBlockManagerInterface;
use Rabble\ContentBundle\Form\EventSubscriber\ResizeContentBlockCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RabbleContentBlockCollectionType extends AbstractType
{
    private ContentBlockManagerInterface $contentBlockManager;

    public function __construct(ContentBlockManagerInterface $contentBlockManager)
    {
        $this->contentBlockManager = $contentBlockManager;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('max_size', null);
        $resolver->setAllowedTypes('max_size', ['null', 'int']);
        $resolver->setRequired('content_blocks');
        $resolver->setAllowedTypes('content_blocks', ['array']);
        $resolver->addNormalizer('content_blocks', function (OptionsResolver $resolver, array $blockTypes) {
            $contentBlocks = [];
            foreach ($blockTypes as $blockType) {
                $contentBlocks[$blockType] = $this->contentBlockManager->get($blockType);
            }

            return $contentBlocks;
        });
        $resolver->setDefaults([
            'prototype_name' => '__name__',
            'allow_extra_fields' => true,
            'invalid_message' => function (Options $options, $previousValue) {
                return ($options['legacy_error_messages'] ?? true)
                    ? $previousValue
                    : 'The collection is invalid.';
            },
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $prototypeName = $options['prototype_name'].spl_object_hash($builder);
        $prototypes = [];
        /** @var ContentBlock $contentBlock */
        foreach ($options['content_blocks'] as $contentBlock) {
            $prototypes[$contentBlock->getName()] = $builder->create($prototypeName, ContentBlockContainerType::class, [
                'content_block' => $contentBlock,
            ])->getForm();
        }
        $builder->setAttribute('prototypes', $prototypes);
        $builder->setAttribute('prototypeName', $prototypeName);

        $resizeListener = new ResizeContentBlockCollectionListener($options['content_blocks']);

        $builder->addEventSubscriber($resizeListener);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $prototypeName = $form->getConfig()->getAttribute('prototypeName');
        /** @var FormInterface[] $prototypes */
        $prototypes = $form->getConfig()->getAttribute('prototypes');
        $view->vars['prototypes'] = [];
        foreach ($prototypes as $blockName => $prototype) {
            $view->vars['prototypes'][$blockName] = $prototype->setParent($form)->createView($view);
        }
        $view->vars['attr'] = ['class' => 'sortable', 'data-max-size' => $options['max_size'] ?? -1];
        $view->vars['max_size'] = $options['max_size'];
        $view->vars['type'] = 'collection';
        $view->vars['prototype_name'] = $prototypeName;
        $view->vars['content_blocks'] = $options['content_blocks'];
        $view->vars['allow_delete'] = true;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormInterface[] $prototypes */
        $prototypes = $form->getConfig()->getAttribute('prototypes');
        foreach ($prototypes as $blockName => $prototype) {
            if ($view->vars['prototypes'][$blockName]->vars['multipart']) {
                $view->vars['multipart'] = true;
            }
        }
    }
}
