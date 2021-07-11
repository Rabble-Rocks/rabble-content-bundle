<?php

namespace Rabble\ContentBundle\Builder;

use Rabble\AdminBundle\Builder\AdminBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RabbleContentBuilder implements AdminBuilderInterface
{
    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function build(string $env, InputInterface $input, OutputInterface $output): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $output->writeln('Creating Elasticsearch content indexes...');
        $application->run(new ArrayInput([
            'command' => 'rabble:content:index:create',
            '--env' => $input->getOption('env'),
        ]), $output);

        $output->writeln('Adding PHPCR node types...');
        $application->run(new ArrayInput([
            'command' => 'rabble:content:register_node_types',
            '--env' => $input->getOption('env'),
        ]), $output);
    }
}
