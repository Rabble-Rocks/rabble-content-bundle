<?php

namespace Rabble\ContentBundle\Form;

use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentListConfigurationType extends AbstractType
{
    private ContentTypeManagerInterface $contentTypeManager;
    private TranslatorInterface $translator;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        TranslatorInterface $translator
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $contentTypes = [];
        foreach ($this->contentTypeManager->all() as $contentType) {
            if ($contentType->hasAttribute('label_'.$this->translator->getLocale())) {
                $label = $contentType->getAttribute('label_'.$this->translator->getLocale());
            } else {
                $label = $this->translator->trans('menu.content.'.$contentType->getName(), [], $contentType->hasAttribute(ContentType::TRANSLATION_DOMAIN_ATTRIBUTE) ? $contentType->getAttribute(ContentType::TRANSLATION_DOMAIN_ATTRIBUTE) : 'messages');
            }
            $contentTypes[$label] = $contentType->getName();
        }
        $builder->add('contentType', ChoiceType::class, [
            'label' => 'form.content_type',
            'choices' => $contentTypes,
            'required' => false,
            'translation_domain' => 'RabbleContentBundle',
            'choice_translation_domain' => false,
        ]);
        $builder->add('children', PageReferenceType::class, [
            'label' => 'form.children',
            'required' => false,
            'translation_domain' => 'RabbleContentBundle',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'content_list_configuration';
    }
}
