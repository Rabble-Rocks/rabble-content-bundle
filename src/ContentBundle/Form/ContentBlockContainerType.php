<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\ContentBlock\ContentBlock;
use Rabble\FieldTypeBundle\Form\FieldContainerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentBlockContainerType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getParent(): string
    {
        return FieldContainerType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('content_block');
        $resolver->setAllowedTypes('content_block', [ContentBlock::class]);
        $resolver->setDefault('fields', []);
        $resolver->setDefault('translation_domain', false);
        $resolver->setNormalizer('fields', static function (Options $options, array $fields) {
            /** @var ContentBlock $contentBlock */
            $contentBlock = $options['content_block'];
            if ([] === $fields && [] !== $contentBlock->getFields()) {
                return $contentBlock->getFields();
            }

            return $fields;
        });
        $resolver->setNormalizer('label', function (Options $options) {
            /** @var ContentBlock $contentBlock */
            $contentBlock = $options['content_block'];
            if ($contentBlock->hasAttribute($labelAttribute = 'label_'.$this->translator->getLocale())) {
                return $contentBlock->getAttribute($labelAttribute);
            }
            if ($contentBlock->hasAttribute('translation_domain')) {
                return $this->translator->trans('content_block.'.$contentBlock->getName(), [], $contentBlock->getAttribute('translation_domain'));
            }

            return $contentBlock->getName();
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('rabble:content_block', HiddenType::class, [
            'data' => $options['content_block']->getName(),
        ]);
    }

    public function getBlockPrefix()
    {
        return 'rabble_content_block_item';
    }
}
