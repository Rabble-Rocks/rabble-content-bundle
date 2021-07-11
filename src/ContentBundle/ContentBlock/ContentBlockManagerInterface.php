<?php

namespace Rabble\ContentBundle\ContentBlock;

interface ContentBlockManagerInterface
{
    public function add(ContentBlock $contentBlock): void;

    public function has(string $name): bool;

    public function get(string $name): ContentBlock;

    /**
     * @return ContentBlock[]
     */
    public function all(): array;

    public function remove(string $name): void;
}
