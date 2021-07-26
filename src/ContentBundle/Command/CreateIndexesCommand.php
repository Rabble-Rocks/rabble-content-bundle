<?php

namespace Rabble\ContentBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use ONGR\ElasticsearchBundle\Service\IndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIndexesCommand extends Command
{
    protected static $defaultName = 'rabble:content:index:create';

    /** @var ArrayCollection<IndexService> */
    private ArrayCollection $indexes;

    /**
     * @param ArrayCollection<IndexService> $indexes
     */
    public function __construct(
        ArrayCollection $indexes
    ) {
        parent::__construct();
        $this->indexes = $indexes;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $application = clone $this->getApplication();
        /** @var IndexService $index */
        foreach ($this->indexes as $index) {
            $commandInput = new ArrayInput([
                'command' => 'ongr:es:index:create',
                '--if-not-exists' => true,
                '-i' => $index->getIndexSettings()->getAlias(),
            ]);
            $application->run($commandInput, $output);
        }

        return 0;
    }
}
