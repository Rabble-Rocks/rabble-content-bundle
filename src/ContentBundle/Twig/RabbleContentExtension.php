<?php

namespace Rabble\ContentBundle\Twig;

use Rabble\ContentBundle\Content\Structure\StructureBuilder;
use Rabble\ContentBundle\Persistence\Manager\ContentManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RabbleContentExtension extends AbstractExtension
{
    private ContentManagerInterface $contentManager;
    private StructureBuilder $structureBuilder;
    private RequestStack $requestStack;

    public function __construct(
        ContentManagerInterface $contentManager,
        StructureBuilder $structureBuilder,
        RequestStack $requestStack
    ) {
        $this->contentManager = $contentManager;
        $this->structureBuilder = $structureBuilder;
        $this->requestStack = $requestStack;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('rabble_content_load', [$this, 'loadContent']),
        ];
    }

    public function loadContent(string $uuid, ?string $locale = null): ?array
    {
        if (null === $locale && null !== $request = $this->requestStack->getCurrentRequest()) {
            $locale = $request->getLocale();
        }
        $this->contentManager->setLocale($locale);
        $content = $this->contentManager->find($uuid);
        if (null === $content) {
            return null;
        }

        return $this->structureBuilder->build($content, StructureBuilder::TARGET_WEBSITE);
    }
}
