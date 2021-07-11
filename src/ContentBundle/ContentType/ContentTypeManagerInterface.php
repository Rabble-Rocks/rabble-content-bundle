<?php

namespace Rabble\ContentBundle\ContentType;

use Rabble\ContentBundle\DocumentFieldsProvider\DocumentFieldsProviderInterface;

/**
 * The content type manager contains a collection of all content types within
 * the system. The system should expect content types to be indexed by name.
 */
interface ContentTypeManagerInterface extends DocumentFieldsProviderInterface
{
    /**
     * Adds a content type to the manager.
     */
    public function add(ContentType $contentType): void;

    /**
     * Checks if a content type exists within the manager with the given name.
     */
    public function has(string $name): bool;

    /**
     * Finds all content types within the manager containing a specific tag.
     *
     * @return ContentType[]
     */
    public function findByTag(string $tag): array;

    /**
     * Returns a single content type by name.
     */
    public function get(string $name): ContentType;

    /**
     * Returns all content types indexed [$name => $contentType].
     *
     * @return ContentType[]
     */
    public function all(): array;

    /**
     * Removes a content type.
     */
    public function remove(string $name): void;
}
