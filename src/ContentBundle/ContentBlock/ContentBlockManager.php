<?php

namespace Rabble\ContentBundle\ContentBlock;

class ContentBlockManager implements ContentBlockManagerInterface
{
    /** @var ContentBlock[] */
    protected array $contentBlocks = [];

    /**
     * @param ContentBlock[] $contentBlocks
     */
    public function __construct(array $contentBlocks = [])
    {
        foreach ($contentBlocks as $contentBlock) {
            $this->add($contentBlock);
        }
    }

    public function add(ContentBlock $contentBlock): void
    {
        $this->contentBlocks[$contentBlock->getName()] = $contentBlock;
    }

    public function has(string $name): bool
    {
        return isset($this->contentBlocks[$name]);
    }

    public function get(string $name): ContentBlock
    {
        return $this->contentBlocks[$name];
    }

    public function all(): array
    {
        return $this->contentBlocks;
    }

    public function remove(string $name): void
    {
        unset($this->contentBlocks[$name]);
    }
}
