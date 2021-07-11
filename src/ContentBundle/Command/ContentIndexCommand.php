<?php

namespace Rabble\ContentBundle\Command;

use Jackalope\Node;
use Jackalope\Session;
use Rabble\ContentBundle\Content\ContentIndexer;
use Rabble\ContentBundle\ContentType\ContentTypeManagerInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContentIndexCommand extends Command
{
    protected static $defaultName = 'rabble:content:index';

    private ContentTypeManagerInterface $contentTypeManager;
    private Session $session;
    private ContentManager $contentManager;
    private ContentIndexer $contentIndexer;
    private string $defaultLocale;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        Session $session,
        ContentManager $contentManager,
        ContentIndexer $contentIndexer,
        string $defaultLocale
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->session = $session;
        $this->contentManager = $contentManager;
        $this->contentIndexer = $contentIndexer;
        $this->defaultLocale = $defaultLocale;

        parent::__construct();
    }

    public function configure()
    {
        $this->addArgument('locale', InputArgument::OPTIONAL, 'Locale to index documents for', $this->defaultLocale);
        $this->addOption('full-reset', null, InputOption::VALUE_NONE, 'Perform a full reset, clearing the entire index beforehand.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->contentManager->setLocale($input->getArgument('locale'));
        if ($input->getOption('full-reset')) {
            $this->contentIndexer->reset();
        }
        foreach ($this->contentTypeManager->all() as $contentType) {
            $node = $this->session->getNode(sprintf('/content/%s', $contentType->getName()));
            /** @var Node $content */
            foreach ($node->getNodes() as $content) {
                $this->contentIndexer->index($this->contentManager->find($content->getPath()));
            }
        }
        $this->contentIndexer->commit();

        return 0;
    }
}
