<?php

namespace Rabble\ContentBundle\Routing;

use Rabble\AdminBundle\Routing\Event\RoutingEvent;

class RoutingListener
{
    public function onRoutingLoad(RoutingEvent $event)
    {
        $event->addResources('xml', ['@RabbleContentBundle/Resources/config/routing.xml']);
    }
}
