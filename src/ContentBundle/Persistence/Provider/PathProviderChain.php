<?php

namespace Rabble\ContentBundle\Persistence\Provider;

use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Webmozart\Assert\Assert;

class PathProviderChain implements PathProviderInterface
{
    private PathProviderInterface $fallbackProvider;
    /** @var PathProviderInterface[] */
    private array $providers;

    public function __construct(PathProviderInterface $fallbackProvider, array $providers = [])
    {
        $this->fallbackProvider = $fallbackProvider;
        $this->providers = $providers;
    }

    public function addProvider(PathProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }

    public function setProviders(array $providers): void
    {
        $this->providers = $providers;
    }

    public function provide(AbstractPersistenceDocument $document): string
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($document)) {
                return $provider->provide($document);
            }
        }
        Assert::true($this->fallbackProvider->supports($document));

        return $this->fallbackProvider->provide($document);
    }

    public function supports(AbstractPersistenceDocument $document): bool
    {
        return true;
    }
}
