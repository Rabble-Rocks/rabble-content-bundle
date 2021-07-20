<?php

namespace Rabble\ContentBundle\Controller\Action;

use Rabble\ContentBundle\Content\Slug\SlugProviderInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugProviderAction
{
    private ContentManager $contentManager;
    private SluggerInterface $slugger;
    private SlugProviderInterface $slugProvider;

    public function __construct(
        SlugProviderInterface $slugProvider,
        SluggerInterface $slugger,
        ContentManager $contentManager
    ) {
        $this->slugProvider = $slugProvider;
        $this->slugger = $slugger;
        $this->contentManager = $contentManager;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $content = (string) $request->query->get('content');
        $title = $request->query->get('title');
        $content = $this->contentManager->find($content);
        if (null === $content && !is_string($title)) {
            throw new NotFoundHttpException();
        }

        if (null === $content) {
            return new JsonResponse([
                'value' => '/'.$this->slugger->slug(strtolower($title)),
            ]);
        }

        return new JsonResponse([
            'value' => $this->slugProvider->provide($content, $title),
        ]);
    }
}
