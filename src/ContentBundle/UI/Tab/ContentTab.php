<?php

namespace Rabble\ContentBundle\UI\Tab;

use Rabble\AdminBundle\Ui\Panel\Tab;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ContentTab extends Tab
{
    private FormView $formView;
    private ContentDocument $contentDocument;
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, array $options = [])
    {
        $this->translator = $translator;
        parent::__construct($options);
        if (false !== $this->options['translation_domain']) {
            $this->setLabel($this->translator->trans($this->getLabel(), [], $this->options['translation_domain']));
        }
    }

    public function setFormView(FormView $formView)
    {
        $this->formView = $formView;
    }

    public function setContentDocument(ContentDocument $contentDocument)
    {
        $this->contentDocument = $contentDocument;
    }

    public function render(Environment $twig): string
    {
        $content = $twig->render($this->options['contentTemplate'], [
            'form' => $this->formView,
            'content' => $this->contentDocument,
            'component' => $this->options['component'],
        ]);
        $this->setContent($content);

        return parent::render($twig);
    }

    public function getContentType(): ?string
    {
        return $this->options['contentType'];
    }

    public function setContentType(?string $contentType): void
    {
        $this->options['contentType'] = $contentType;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('content', '');
        $resolver->setDefault('translation_domain', false);
        $resolver->setDefault('contentType', null);
        $resolver->setDefault('component', 'default');
        $resolver->setRequired(['contentTemplate']);
        $resolver->setAllowedTypes('contentType', ['string', 'null']);
        $resolver->setAllowedTypes('contentTemplate', ['string']);
        $resolver->setAllowedTypes('component', ['string']);
        $resolver->setAllowedValues('translation_domain', [false, function ($value) {
            return is_string($value);
        }]);
    }
}
