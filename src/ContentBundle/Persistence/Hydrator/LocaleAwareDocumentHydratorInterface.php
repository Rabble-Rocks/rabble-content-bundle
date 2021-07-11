<?php

namespace Rabble\ContentBundle\Persistence\Hydrator;

use Symfony\Contracts\Translation\LocaleAwareInterface;

interface LocaleAwareDocumentHydratorInterface extends DocumentHydratorInterface, LocaleAwareInterface
{
}
