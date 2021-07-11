<?php

namespace Rabble\ContentBundle\Controller;

use Rabble\AdminBundle\EventListener\RouterContextSubscriber;
use Rabble\AdminBundle\Ui\Panel\TabbedPanel;
use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\Form\ContentFormType;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Rabble\ContentBundle\UI\Tab\ContentTab;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends AbstractController
{
    private ContentManager $contentManager;
    private TabbedPanel $formPanel;

    public function __construct(
        ContentManager $contentManager,
        TabbedPanel $formPanel
    ) {
        $this->contentManager = $contentManager;
        $this->formPanel = $formPanel;
    }

    public function indexAction(Request $request, ContentType $contentType)
    {
        return $this->render('@RabbleContent/Content/index.html.twig', [
            'contentType' => $contentType,
        ]);
    }

    public function createAction(Request $request, ContentType $contentType)
    {
        $this->contentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        $content = new ContentDocument();
        $content->setContentType($contentType->getName());
        $form = $this->createForm(
            ContentFormType::class,
            $content,
            [
                'fields' => $contentType->getFields(),
            ]
        )->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->contentManager->persist($content);
            $this->contentManager->flush();
            $this->addFlash('success', 'The content has been saved.');

            return $this->redirectToRoute('rabble_admin_content_edit', ['content' => $content->getUuid(), 'contentType' => $contentType->getName()]);
        }
        $formView = $form->createView();
        foreach ($this->formPanel->getTabs() as $tab) {
            if ($tab instanceof ContentTab) {
                $tab->setContentDocument($content);
                $tab->setFormView($formView);
            }
        }

        return $this->render($contentType->getAttribute('template') ?? '@RabbleContent/Content/form.html.twig', [
            'form' => $formView,
            'formPanel' => $this->formPanel,
            'content' => $content,
            'action' => 'create',
            'contentType' => $contentType,
        ]);
    }

    public function editAction(Request $request, ContentType $contentType, $content)
    {
        $this->contentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        $content = $this->contentManager->find($content);
        if (null === $content) {
            throw new NotFoundHttpException();
        }
        $form = $this->createForm(
            ContentFormType::class,
            $content,
            ['fields' => $contentType->getFields()]
        )->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->contentManager->flush();
            $this->addFlash('success', 'The content has been saved.');
            $form = $this->createForm(
                ContentFormType::class,
                $content,
                ['fields' => $contentType->getFields()]
            );
        }
        $formView = $form->createView();
        foreach ($this->formPanel->getTabs() as $tab) {
            if ($tab instanceof ContentTab) {
                $tab->setContentDocument($content);
                $tab->setFormView($formView);
            }
        }

        return $this->render($contentType->getAttribute('template') ?? '@RabbleContent/Content/form.html.twig', [
            'form' => $formView,
            'formPanel' => $this->formPanel,
            'content' => $content,
            'action' => 'edit',
            'contentType' => $contentType,
        ]);
    }

    public function deleteAction(Request $request, ContentType $contentType, $content)
    {
        $this->contentManager->setLocale($request->attributes->get(RouterContextSubscriber::CONTENT_LOCALE_KEY));
        $content = $this->contentManager->find($content);
        if (null === $content) {
            throw new NotFoundHttpException();
        }
        $this->contentManager->remove($content);
        $this->contentManager->flush();

        return $this->redirectToRoute('rabble_admin_content_index', ['contentType' => $contentType->getName()]);
    }
}
