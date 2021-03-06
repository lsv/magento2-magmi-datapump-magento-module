<?php

namespace Lsv\Magmi2ImportTest\Console\Command;

use Lsv\Magmi2ImportTest\Import;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestImportCommand extends Command
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

    protected function configure(): void
    {
        $this
            ->setName('lsv:magmi2importtest:import')
            ->addOption('speedtest', null, InputOption::VALUE_NONE, 'Run the speed test')
            ->setDescription('Test magmi');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->import->execute($input, $output);
    }
}
