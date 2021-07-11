<?php

namespace Rabble\ContentBundle\EventListener;

use Rabble\ContentBundle\Event\DefaultFieldTypesEvent;
use Rabble\ContentBundle\FieldType\SlugType;
use Rabble\FieldTypeBundle\FieldType\StringType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class DefaultFieldTypesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DefaultFieldTypesEvent::class => 'registerDefaultFields',
        ];
    }

    public function registerDefaultFields(DefaultFieldTypesEvent $event): void
    {
        $event->addFieldType(new StringType([
            'name' => 'title',
            'label' => 'content.title',
            'translatable' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter a title.',
                ]),
            ],
            'attr' => [
                'class' => 'rabble-title',
            ],
            'translation_domain' => 'RabbleContentBundle',
        ]));
        $event->addFieldType(new SlugType([
            'name' => 'slug',
            'label' => 'content.slug',
            'translation_domain' => 'RabbleContentBundle',
            'required' => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter a slug.',
                ]),
                new Regex([
                    // Valid: /, /hello/world, /hello/world.html
                    // invalid: /hello/world/, hello/world, /hello/.
                    'pattern' => '/^\/(?:[a-zA-Z0-9\._-]*(?:\/[a-zA-Z0-9_-])?)+$/',
                    'message' => 'The slug is invalid. It should only contain forward slashes, letters, numbers, dashes and underscores.',
                ]),
            ],
            'translatable' => true,
        ]));
    }
}
