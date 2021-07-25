<?php

namespace Rabble\ContentBundle\Command;

use Jackalope\Node;
use Jackalope\Session;
use Rabble\ContentBundle\Content\ContentIndexer;
use Rabble\ContentBundle\Persistence\Document\AbstractPersistenceDocument;
use Rabble\ContentBundle\Persistence\Document\ContentDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocument;
use Rabble\ContentBundle\Persistence\Document\StructuredDocumentInterface;
use Rabble\ContentBundle\Persistence\Manager\ContentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ContentIndexCommand extends Command
{
    protected static $defaultName = 'rabble:content:index';

    private Session $session;
    private ContentManager $contentManager;
    private ContentIndexer $contentIndexer;
    private string $defaultLocale;

    public function __construct(
        Session $session,
        ContentManager $contentManager,
        ContentIndexer $contentIndexer,
        string $defaultLocale
    ) {
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
        if ($this->session->nodeExists(ContentDocument::ROOT_NODE)) {
            $this->doIndex($this->session->getNode(ContentDocument::ROOT_NODE)->getNodes());
        }
        if ($this->session->nodeExists(StructuredDocument::ROOT_NODE)) {
            $this->doIndex($this->session->getNode(StructuredDocument::ROOT_NODE)->getNodes());
        }
        $this->contentIndexer->commit();

        return 0;
    }

    protected function doIndex(iterable $nodes): void
    {
        /** @var Node $content */
        foreach ($nodes as $content) {
            $content = $this->contentManager->find($content->getPath());
            if ($content instanceof AbstractPersistenceDocument) {
                $this->contentIndexer->index($content);
            }
            if ($content instanceof StructuredDocumentInterface) {
                $node = $this->session->getNode($content->getPath());
                $this->doIndex($node->getNodes());
            }
        }
    }
}
