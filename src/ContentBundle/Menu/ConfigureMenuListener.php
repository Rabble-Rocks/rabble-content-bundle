<?php

namespace Rabble\ContentBundle\Menu;

use Rabble\AdminBundle\Menu\Event\ConfigureMenuEvent;
use Rabble\ContentBundle\ContentType\ContentType;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigureMenuListener
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

    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        $menu = $event->getRootItem();
        foreach ($this->contentTypeManager->all() as $contentType) {
            $translationDomain = $contentType->hasAttribute(ContentType::TRANSLATION_DOMAIN_ATTRIBUTE) ? $contentType->getAttribute(ContentType::TRANSLATION_DOMAIN_ATTRIBUTE) : 'messages';
            $label = 'menu.content.'.$contentType->getName();
            if ($contentType->hasAttribute('label_'.$this->translator->getLocale())) {
                $label = $contentType->getAttribute('label_'.$this->translator->getLocale());
                $translationDomain = false;
            }
            $extras = [
                'translation_domain' => $translationDomain,
                'routes' => [
                    [
                        'route' => 'rabble_admin_content_create',
                        'parameters' => ['contentType' => $contentType->getName()],
                    ],
                    [
                        'route' => 'rabble_admin_content_edit',
                        'parameters' => ['contentType' => $contentType->getName()],
                    ],
                ],
            ];
            if ($contentType->hasAttribute('icon')) {
                $extras['icon'] = $contentType->getAttribute('icon');
            }
            if ($contentType->hasAttribute('icon_color')) {
                $extras['icon_color'] = $contentType->getAttribute('icon_color');
            }

            $menu->addChild('rabble_content_'.$contentType->getName(), [
                'label' => $label,
                'route' => 'rabble_admin_content_index',
                'routeParameters' => [
                    'contentType' => $contentType->getName(),
                ],
                'extras' => $extras,
            ]);
        }

        $menu->addChild('rabble_content_structure', [
            'label' => 'menu.content_structure.index',
            'route' => 'rabble_admin_content_structure_index',
            'extras' => [
                'translation_domain' => 'RabbleContentBundle',
                'icon' => 'fa fa-code',
                'icon_color' => 'purple-400',
            ],
        ]);
    }
}
