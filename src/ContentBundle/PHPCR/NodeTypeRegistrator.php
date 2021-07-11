<?php

namespace Rabble\ContentBundle\PHPCR;

use PHPCR\SessionInterface;
use Rabble\ContentBundle\Content\Translator\ContentTranslatorInterface;

final class NodeTypeRegistrator
{
    private string $namespace = ContentTranslatorInterface::PHPCR_NAMESPACE;
    private string $namespaceUri = ContentTranslatorInterface::PHPCR_NAMESPACE_URI;
    private string $namespaceRabble = 'rabble';
    private string $namespaceUriRabble = 'http://rabble.rocks/namespace/rabble';

    public function registerNodeTypes(SessionInterface $session)
    {
        $cnd = "<{$this->namespace}='{$this->namespaceUri}'>\n";
        $cnd .= "<{$this->namespaceRabble}='{$this->namespaceUriRabble}'>";

        $nodeTypeManager = $session->getWorkspace()->getNodeTypeManager();
        $nodeTypeManager->registerNodeTypesCnd($cnd, true);
    }
}
