<?php
namespace Ciandt;

use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Exception;
use PHPUnit\Framework\Assert;
use Postcon\BehatShellExtension\ShellContext;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Command Line Interface Context
 *
 * @author Bruno Wowk <bwowk@ciandt.com>
 */
class CliContext extends ShellContext
{

    /** @var array */
    private $config;

    /** @var Process */
    private $last_process;

    private $processes;

    /** @var string */
    private $featurePath;

    private $prefix = null;
    
    private $pid;

    private $timeout = 10;

    /**
     * @param array $config
     */
    public function initializeConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @BeforeScenario
     *
     * @param ScenarioScope $scope
     */
    public function initializeFeatureFilePath(ScenarioScope $scope)
    {
        $this->featurePath = dirname($scope->getFeature()->getFile());
    }

    /**
     * @When I run :command with :arguments
     * @When I run :command
     * @When I run :command on :server
     * @When User runs :command
     * @When User runs :command with :arguments
     * @Given I ran :command with :arguments
     * @Given I ran :command
     * @Given I ran :command on :server
     * @Given the user ran :command
     * @Given the user ran :command with :arguments
     *
     * @param string $command
     * @param string $server
     *
     * @throws Exception
     */
    public function iRun($command, $arguments = null, $server = 'default')
    {
        if (!isset($this->config[$server])) {
            throw new Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        if (!is_null($this->prefix)){
            $command = $this->prefix . " " . $command;
        }

        $process  = $this->createProcess($command, $this->config[$server]);
        if ($arguments){
            $process->setInput($arguments);
        }
        $process->start();
        $this->pid = $process->getPid();
        $process->wait();
        $this->last_process = $process;
        $this->processes[] = $process;
    }

    /**
     * @When I run :command answering with:
     */
    public function iRunWithMultipleInput($command, TableNode $arguments)
    {
        if (!isset($this->config['default'])) {
            throw new Exception(sprintf('Configuration not found for server "%s"', 'default'));
        }

        if (!is_null($this->prefix)){
            $command = $this->prefix . " " . $command;
        }

        $process  = $this->createProcess($command, $this->config['default']);
        $stream = new InputStream();
        $process->setInput($stream);
        $answers = $arguments->getRows();
        $process->start($this->answerPrompts($process,$answers,$stream));
        $this->pid = $process->getPid();
        $process->wait();
        $this->last_process = $process;
        $this->processes[] = $process;
    }

    /**
     * @When I start :command answering with:
     */
    public function iStartWithMultipleInput($command, TableNode $arguments)
    {
        if (!isset($this->config['default'])) {
            throw new Exception(sprintf('Configuration not found for server "%s"', 'default'));
        }

        if (!is_null($this->prefix)){
            $command = $this->prefix . " " . $command;
        }

        $process  = $this->createProcess($command, $this->config['default']);
        $stream = new InputStream();
        $process->setInput($stream);
        $answers = $arguments->getRows();
        $process->start($this->answerPrompts($process,$answers,$stream));
        $this->pid = $process->getPid();
        $this->last_process = $process;
        $this->processes[] = $process;
    }

    protected function answerPrompts(Process $process, &$answers, InputStream $inputStream){
        return function ($type, $output) use ($process, &$answers, $inputStream) {

            if ($inputStream->isClosed()) {
                return;
            }

            if (!$process->isRunning()){
                $inputStream->close();
                return;
            }

            $prompt = $answers[0][0];
            if (strpos($output, $prompt) !== FALSE) {
                $input = array_shift($answers)[1];
                $inputStream->write($input . "\n");
                if (empty($answers)){
                    $inputStream->close();
                }
            }

        };
    }

    /**
     * @Given it's pid is :placeholder
     *
     * @throws Exception
     */
    public function iStorePidInPlaceholder($placeholder)
    {
        if (!isset($this->pid)) {
            throw new RuntimeException("No process running."
                . " Did you start one yet?");
        }
        
        if (!isset($this->placeholders)) {
            throw new RuntimeException("Cannot access the Placeholders Repository."
                . " Is the Placeholders extension enabled?");
        }
      
        $this->placeholders->setPlaceholder($placeholder,$this->pid);
    }

    protected function getLastProcess(){
        if (!isset($this->last_process)) {
            throw new RuntimeException("No process running."
                . " Did you start one yet?");
        }
        return $this->last_process;
    }

    /**
     * @When I store it's output in :placeholder
     *
     * @throws Exception
     */
    public function iStoreOutputInPlaceholder($placeholder)
    {

        if (!isset($this->placeholders)) {
            throw new RuntimeException("Cannot access the Placeholders Repository."
                . " Is the Placeholders extension enabled?");
        }

        $process = $this->getProcess;
        $output = $process->getOutput();
        $this->placeholders->setPlaceholder($placeholder,$output);
    }

    /**
     * @When I copy file :file to :directory
     * @When I copy file :file to :directory on :server
     *
     * @param string $file
     * @param string $directory
     * @param string $server
     *
     * @throws Exception
     */
    public function iCopyFileTo($file, $directory, $server = 'default')
    {
        if (!isset($this->config[$server])) {
            throw new Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        $sourceFile      = $this->featurePath . \DIRECTORY_SEPARATOR . ltrim($file, \DIRECTORY_SEPARATOR);
        $destinationFile = $directory . \DIRECTORY_SEPARATOR . basename($file);

        switch ($this->config[$server]['type']) {
            case 'remote':
                $process = $this->createScpProcess($sourceFile, $directory, $this->config[$server]);
                $process->run();
                break;

            case 'docker':
                $process = $this->createDockerCpProcess($sourceFile, $directory, $this->config[$server]);
                $process->run();
                break;

            default:
                copy($sourceFile, $destinationFile);
        }
    }

    /**
     * @Then it should pass
     */
    public function itShouldPass()
    {
        if ($this->last_process->isSuccessful()) {
            throw new Exception(sprintf(
                    "Process failed: %s\n%s\n%s",
                    $this->last_process->getCommandLine(),
                    $this->last_process->getOutput(),
                    $this->last_process->getErrorOutput()
                ));
        }
    }

    /**
     * @Then it should fail
     */
    public function itShouldFail()
    {
        if (true === $this->last_process->isSuccessful()) {
            throw new Exception(sprintf('Process passed: %s', $this->last_process->getCommandLine()));
        }
    }
    
    
    /**
     * @Then it should exit with code ":code"
     */
    public function itShouldExitWith($code)
    {
        if (!($code == $this->last_process->getExitCode())) {
            throw new Exception(sprintf('Process exited with: %s. Expected %s.', $this->last_process->getExitCode(), $code));
        }
    }

    /**
     * @Then it's output should be ":expected"
     * @Then it's output should be:
     *
     * @param string|PyStringNode $expected
     *
     * The expected content string may contain the following placeholders:
     * %e: Represents a directory separator, for example / on Linux.
     * %s: One or more of anything (character or white space) except the end of line character.
     * %S: Zero or more of anything (character or white space) except the end of line character.
     * %a: One or more of anything (character or white space) including the end of line character.
     * %A: Zero or more of anything (character or white space) including the end of line character.
     * %w: Zero or more white space characters.
     * %i: A signed integer value, for example +3142, -3142.
     * %d: An unsigned integer value, for example 123456.
     * %x: One or more hexadecimal character. That is, characters in the range 0-9, a-f, A-F.
     * %f: A floating point number, for example: 3.142, -3.142, 3.142E-10, 3.142e+10.
     * %c: A single character of any sort.
     *
     * @throws Exception
     */
    public function iSee($expected)
    {
        $actual   = trim($this->last_process->getOutput());
        if ($expected instanceof PyStringNode){
            $expected = $expected->getRaw();
        }
        $expected = trim($expected);

        Assert::assertStringMatchesFormat($expected, $actual);

    }

    /**
     * @Then it's output should contain ":expected"
     * @Then it's output should contain:
     *
     * @param string|PyStringNode $expected
     *
     * The expected content string may contain the following placeholders:
     * %e: Represents a directory separator, for example / on Linux.
     * %s: One or more of anything (character or white space) except the end of line character.
     * %S: Zero or more of anything (character or white space) except the end of line character.
     * %a: One or more of anything (character or white space) including the end of line character.
     * %A: Zero or more of anything (character or white space) including the end of line character.
     * %w: Zero or more white space characters.
     * %i: A signed integer value, for example +3142, -3142.
     * %d: An unsigned integer value, for example 123456.
     * %x: One or more hexadecimal character. That is, characters in the range 0-9, a-f, A-F.
     * %f: A floating point number, for example: 3.142, -3.142, 3.142E-10, 3.142e+10.
     * %c: A single character of any sort.
     *
     * @throws Exception
     */
    public function iSeeSomethingLike($expected)
    {
        $actual   = trim($this->last_process->getOutput());
        if ($expected instanceof PyStringNode){
            $expected = $expected->getRaw();
        }
        $expected = '%A' . trim($expected) . '%A';

        Assert::assertStringMatchesFormat ($expected, $actual);

    }

    /**
     * @Then it's output should not contain ":expected"
     * @Then it's output should not contain:
     *
     * @param string|PyStringNode $expected
     *
     * The expected content string may contain the following placeholders:
     * %e: Represents a directory separator, for example / on Linux.
     * %s: One or more of anything (character or white space) except the end of line character.
     * %S: Zero or more of anything (character or white space) except the end of line character.
     * %a: One or more of anything (character or white space) including the end of line character.
     * %A: Zero or more of anything (character or white space) including the end of line character.
     * %w: Zero or more white space characters.
     * %i: A signed integer value, for example +3142, -3142.
     * %d: An unsigned integer value, for example 123456.
     * %x: One or more hexadecimal character. That is, characters in the range 0-9, a-f, A-F.
     * %f: A floating point number, for example: 3.142, -3.142, 3.142E-10, 3.142e+10.
     * %c: A single character of any sort.
     *
     * @throws Exception
     */
    public function iDoNotSeeSomethingLike($expected)
    {
        $actual   = trim($this->last_process->getOutput());
        if ($expected instanceof PyStringNode){
            $expected = $expected->getRaw();
        }
        $expected = '%A' . trim($expected) . '%A';

        Assert::assertStringNotMatchesFormat($expected, $actual);
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createProcess($command, array $serverConfig)
    {
        switch ($serverConfig['type']) {
            case 'remote':
                $process = $this->createRemoteProcess($command, $serverConfig);
                break;

            case 'docker':
                $process = $this->createDockerProcess($command, $serverConfig);
                break;

            default:
                $process = $this->createLocalProcess($command, $serverConfig);
                break;
        }

        if (null !== $serverConfig['timeout']) {
            $process->setTimeout($serverConfig['timeout']);
        }
        return $process;
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createLocalProcess($command, array $serverConfig)
    {
        return new Process($command, $serverConfig['base_dir']);
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createRemoteProcess($command, array $serverConfig)
    {
        if ($serverConfig['base_dir']) {
            $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
        }

        $command = sprintf(
            '%s %s %s %s',
            $serverConfig['ssh_command'],
            $serverConfig['ssh_options'],
            $serverConfig['ssh_hostname'],
            escapeshellarg($command)
        );

        return new Process($command);
    }

    /**
     * @param string $command
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createDockerProcess($command, array $serverConfig)
    {
        if ($serverConfig['base_dir']) {
            $command = sprintf('cd %s ; %s', $serverConfig['base_dir'], $command);
        }

        $command = sprintf(
            '%s exec %s %s /bin/bash -c %s',
            $serverConfig['docker_command'],
            $serverConfig['docker_options'],
            $serverConfig['docker_containername'],
            escapeshellarg($command)
        );

        return new Process($command);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createScpProcess($source, $destination, array $serverConfig)
    {
        $command = sprintf(
            '%s %s %s %s',
            $serverConfig['scp_command'],
            $serverConfig['ssh_options'],
            escapeshellarg($source),
            escapeshellarg($serverConfig['ssh_hostname'] . ':' . $destination)
        );

        return new Process($command);
    }

    /**
     * @param string $source
     * @param string $destination
     * @param array  $serverConfig
     *
     * @return Process
     */
    private function createDockerCpProcess($source, $destination, array $serverConfig)
    {
        $command = sprintf(
            '%s cp %s %s:%s',
            $serverConfig['docker_command'],
            escapeshellarg($source),
            $serverConfig['docker_containername'],
            escapeshellarg($destination)
        );

        return new Process($command);
    }
    

    /**
     * @When I start :command
     * @When I start :command on :server
     * @When I start :command with :arguments
     * @When User starts :command
     * @When User starts :command with :arguments
     * @Given I started :command with :arguments
     * @Given I started :command
     * @Given I started :command on :server
     * @Given the user started :command
     * @Given the user started :command with :arguments
     *
     * @param string $command
     * @param string $server
     *
     * @throws Exception
     */
    public function iStart($command, $server = 'default', $arguments = null)
    {
        if (!isset($this->config[$server])) {
            throw new Exception(sprintf('Configuration not found for server "%s"', $server));
        }

        $process = $this->createProcess($command, $this->config[$server]);
        if ($arguments){
            $process->setInput($arguments);
        }
        $process->start();
        $this->pid = $process->getPid();
        $this->last_process = $process;
        $this->processes[] = $process;
    }

     /**
     * @Given I prefix the commands with ":prefix"
     */
     public function addPrefix($prefix){
        $this->prefix = $prefix;
    }

    /**
     * @AfterScenario @cleanup_processes
     */
    public function cleanUpProcesses(){
        foreach ($this->processes as $process){
            $process->stop(3, SIGINT);
        }
    }

    /**
     * @AfterScenario @wait_processes
     */
    public function waitProcesses(){
        foreach ($this->processes as $process){
            $process->wait();
        }
    }
    
 

    
}
