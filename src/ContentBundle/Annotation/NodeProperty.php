<?php

namespace Rabble\ContentBundle\Annotation;

use Jackalope\Node;

/**
 * @Annotation
 */
class NodeProperty
{
    public string $name;
    public string $accessor = 'property';
    public ?string $getter = null;
    public ?string $setter = null;

    public function getValue(Node $node)
    {
        switch ($this->accessor) {
            case 'method':
                $getter = $this->getter ?? 'get'.ucfirst($this->name);

                return $node->{$getter}();

            case 'property':
                return $node->hasProperty($this->name) ? $node->getPropertyValue($this->name) : null;

            default:
                throw $this->createException();
        }
    }

    public function setValue(Node $node, $value): void
    {
        switch ($this->accessor) {
            case 'method':
                $setter = $this->setter ?? 'set'.ucfirst($this->name);

                $node->{$setter}($value);

                return;

            case 'property':
                $node->setProperty($this->name, $value);

                return;

            default:
                throw $this->createException();
        }
    }

    private function createException(): \LogicException
    {
        return new \LogicException('Invalid accessor. Allowed values are: \'method\', \'property\'.');
    }
}
