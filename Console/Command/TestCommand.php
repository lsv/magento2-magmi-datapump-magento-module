<?php

namespace Lsv\Magmi2ImportTest\Console\Command;

use Lsv\Magmi2ImportTest\Import;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{

    /**
     * @var Import
     */
    private $import;

    public function __construct(Import $import)
    {
        parent::__construct();
        $this->import = $import;
    }

    protected function configure()
    {
        $this
            ->setName('lsv:magmi2importtest:test')
            ->addOption('speedtest', null, InputOption::VALUE_NONE, 'Only run the speedtest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->import->execute($input, $output);
    }

}
