<?php

namespace Rabble\ContentBundle\Form;

use PHPCR\Util\UUIDHelper;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class PageReferenceType extends AbstractType
{
    private RouterInterface $router;
    private ContentManagerInterface $contentManager;

    public function __construct(RouterInterface $router, ContentManagerInterface $contentManager)
    {
        $this->router = $router;
        $this->contentManager = $contentManager;
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'autocomplete',
                'data-resolver' => $this->router->generate('rabble_admin_content_resolve_page'),
            ],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->resetViewTransformers();
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->getData();
        if (!is_string($data) || !UUIDHelper::isUUID($data)) {
            return;
        }
        $content = $this->contentManager->find($data);
        if (null === $content) {
            return;
        }
        $view->vars['attr']['data-current-value'] = $content->getUuid();
        $view->vars['attr']['data-current-text'] = $content->getTitle();
    }
}
