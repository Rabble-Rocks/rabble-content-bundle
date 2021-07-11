<?php

namespace Rabble\ContentBundle\Command;

use PHPCR\SessionInterface;
use Rabble\ContentBundle\PHPCR\NodeTypeRegistrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterNodeTypesCommand extends Command
{
    protected static $defaultName = 'rabble:content:register_node_types';

    private SessionInterface $session;

    public function __construct(
        SessionInterface $session
    ) {
        $this->session = $session;

        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $registrator = new NodeTypeRegistrator();
        $registrator->registerNodeTypes($this->session);

        $output->writeln('<info>Node types registered.</info>');

        return 0;
    }
}
