<?php

namespace Rabble\ContentBundle\Controller\Action;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugProviderAction
{
    private SluggerInterface $slugger;
    private ContentManager $contentManager;

    public function __construct(
        SluggerInterface $slugger,
        ContentManager $contentManager
    ) {
        $this->slugger = $slugger;
        $this->contentManager = $contentManager;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $content = (string) $request->query->get('content');
        $title = $request->query->get('title');
        $content = $this->contentManager->find($content);
        if (null === $content && null === $title) {
            throw new NotFoundHttpException();
        }
        $slug = '/';
        $slugNodes = [];
        if ($content instanceof AbstractPersistenceDocument) {
            if (!$content->hasProperty('slug')) {
                return new JsonResponse(['value' => $slug]);
            }
            $contentSlug = $content->getProperty('slug');
            if ('/' !== $contentSlug) {
                $slugNodes[] = substr($contentSlug, 1);
            }
        }
        if (is_string($title)) {
            $slugNodes[] = $this->slugger->slug($title);
        }
        $slug .= strtolower(implode('/', $slugNodes));

        return new JsonResponse([
            'value' => $slug,
        ]);
    }
}
