<?php

namespace Rabble\ContentBundle\Form\EventSubscriber;

use Rabble\ContentBundle\ContentBlock\ContentBlock;
use Rabble\ContentBundle\Form\ContentBlockContainerType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This is a modified variant of the ResizeFormListener in the Symfony Form package.
 * For content blocks, we need to discriminate between the type of form we're getting
 * from the rabble:content_block field.
 */
class ResizeContentBlockCollectionListener implements EventSubscriberInterface
{
    /**
     * @var ContentBlock[]
     */
    private array $contentBlocks;

    /**
     * @param ContentBlock[] $contentBlocks
     */
    public function __construct(array $contentBlocks)
    {
        $this->contentBlocks = $contentBlocks;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::SUBMIT => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        // First remove all rows
        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        // Then add all rows again in the correct order
        foreach ($data as $name => $value) {
            if (!isset($value['rabble:content_block']) || !isset($this->contentBlocks[$value['rabble:content_block']])) {
                continue;
            }
            $contentBlock = $this->contentBlocks[$value['rabble:content_block']];
            $form->add($name, ContentBlockContainerType::class, [
                'property_path' => '['.$name.']',
                'content_block' => $contentBlock,
            ]);
        }
    }

    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if (!\is_array($data)) {
            $data = [];
        }

        foreach ($form as $name => $child) {
            if (!isset($data[$name])) {
                $form->remove($name);
            }
        }

        foreach ($data as $name => $value) {
            if (!$form->has($name)) {
                if (!isset($value['rabble:content_block']) || !isset($this->contentBlocks[$value['rabble:content_block']])) {
                    continue;
                }
                $contentBlock = $this->contentBlocks[$value['rabble:content_block']];
                $form->add($name, ContentBlockContainerType::class, [
                    'property_path' => '['.$name.']',
                    'content_block' => $contentBlock,
                ]);
            }
        }
    }

    public function onSubmit(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        $data = $event->getData();

        if (null === $data) {
            $data = [];
        }

        if (!\is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        $toDelete = [];

        foreach ($data as $name => $child) {
            if (!$form->has($name)) {
                $toDelete[] = $name;
            }
        }

        foreach ($toDelete as $name) {
            unset($data[$name]);
        }

        $event->setData($data);
    }
}
