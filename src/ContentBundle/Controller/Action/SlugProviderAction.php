<?php

namespace Rabble\ContentBundle\Controller\Action;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugProviderAction
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $title = $request->query->get('title');
        $slug = '/'.strtolower($this->slugger->slug($title, '-'));

        return new JsonResponse([
            'value' => $slug,
        ]);
    }
}
