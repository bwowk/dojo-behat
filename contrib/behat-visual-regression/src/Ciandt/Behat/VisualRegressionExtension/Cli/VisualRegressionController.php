<?php
namespace Ciandt\Behat\VisualRegressionExtension\Cli;

use Behat\Testwork\Cli\Controller;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Ciandt\Behat\VisualRegressionExtension\Initializer\MinkAwareInitializer;

final class VisualRegressionController implements Controller
{

    private $minkAwareInitializer;
    
    public function __construct(MinkAwareInitializer $minkAwareInitializer)
    {
        $this->minkAwareInitializer = $minkAwareInitializer;
    }

        /**
     * Adds the optional --environment / -e option to the Behat CLI
     *
     * @param SymfonyCommand $command
     */
    public function configure(SymfonyCommand $command)
    {
        $command->addOption(
            'baseline',
            'b',
            InputOption::VALUE_NONE,
            'Sets visual regression checkpoints as baselines'
        );
        $command->addOption(
            'visual-regression',
            null,
            InputOption::VALUE_NONE,
            'Capture and compare visual regression checkpoints'
        );
    }

    /**
     * Gets the environment option and pass it on to the PlaceholdersReplacer
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @todo pass environment to StepTester
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('baseline') && !$input->getOption('visual-regression')) {
            throw new \Exception("the --baseline/-b option requires the --visual-regression option");
        }
        
        $this->minkAwareInitializer->setBaseline($input->getOption('baseline'));
        $this->minkAwareInitializer->setVisualRegression($input->getOption('visual-regression'));
    }
}
