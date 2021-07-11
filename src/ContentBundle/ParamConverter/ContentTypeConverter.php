<?php

namespace Rabble\ContentBundle\ParamConverter;

use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentTypeConverter implements ParamConverterInterface
{
    private ContentTypeManagerInterface $contentTypeManager;

    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * @return bool
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $contentType = $request->attributes->get($configuration->getName());
        if (!$this->contentTypeManager->has($contentType)) {
            throw new NotFoundHttpException(sprintf('Content type \'%s\' does not exist.', $contentType));
        }
        $request->attributes->set($configuration->getName(), $this->contentTypeManager->get($contentType));

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return ContentType::class === $configuration->getClass();
    }
}
